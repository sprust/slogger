<?php

namespace Tests\Modules\Logs\Domain\Actions;

use App\Modules\Logs\Domain\Actions\FindLogErrorStatAction;
use App\Modules\Logs\Domain\Actions\FindReceiverErrorStatAction;
use App\Modules\Logs\Enums\LogTypeEnum;
use Illuminate\Support\Carbon;
use Tests\Modules\Logs\LogsTempDirTrait;
use Tests\TestCase;

class FindReceiverErrorStatActionTest extends TestCase
{
    use LogsTempDirTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTempDir();

        mkdir($this->tempDir . '/receiver');

        config()->set('cache.mutex_store', 'array');
        config()->set('module-logs.sources', [
            ['name' => 'Laravel', 'folder' => $this->tempDir . '/logs', 'pattern' => '*.log', 'type' => LogTypeEnum::Laravel],
            ['name' => 'Receiver', 'folder' => $this->tempDir . '/receiver', 'pattern' => '*.log', 'type' => LogTypeEnum::Receiver],
        ]);

        $this->write(
            'receiver/2026-09-25.log',
            "2026-09-25 10:00:00.100 ERROR before the window\n"
            . "2026-09-25 10:00:20.200 ERROR dial tcp: connection refused\n->stack trace:\n - /app/internal/a.go:140\n<-end of trace\n"
            . "2026-09-25 10:00:30.300 WARN a warning\n"
            . "2026-09-25 10:00:40.400 DEBUG received message with len 944\n"
            . "2026-09-25 10:00:50.500 ERROR server selection error\n"
            . "2026-09-25 10:01:10.600 ERROR after the window\n"
        );

        $this->write('logs/laravel-2026-09-25.log', "[2026-09-25 10:00:45] local.ERROR: an app error \n");
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();

        parent::tearDown();
    }

    public function testErrorsOfTheReceiverInTheWindowAreCounted(): void
    {
        $stat = $this->app->make(FindReceiverErrorStatAction::class)->handle($this->at('10:00:10'), $this->at('10:01:00'));

        $this->assertSame(2, $stat->count);
        $this->assertSame('server selection error', $stat->lastMessage);
    }

    public function testTheMessageIsTheFirstLineWithoutTheTrace(): void
    {
        $stat = $this->app->make(FindReceiverErrorStatAction::class)->handle($this->at('10:00:10'), $this->at('10:00:45'));

        $this->assertSame(1, $stat->count);
        $this->assertSame('dial tcp: connection refused', $stat->lastMessage);
    }

    public function testTheAppErrorsLeaveTheReceiverOut(): void
    {
        $stat = $this->app->make(FindLogErrorStatAction::class)->handle($this->at('10:00:10'), $this->at('10:01:00'));

        $this->assertSame(1, $stat->count);
        $this->assertSame('an app error', $stat->lastMessage);
    }

    private function at(string $time): Carbon
    {
        return new Carbon('2026-09-25 ' . $time, 'UTC');
    }

    private function write(string $relativePath, string $contents): void
    {
        $path = sprintf('%s/%s', $this->tempDir, $relativePath);

        file_put_contents($path, $contents);

        touch($path, gmmktime(10, 5, 0, 9, 25, 2026));
    }
}
