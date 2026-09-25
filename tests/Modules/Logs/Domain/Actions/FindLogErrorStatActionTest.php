<?php

namespace Tests\Modules\Logs\Domain\Actions;

use App\Modules\Logs\Domain\Actions\FindLogErrorStatAction;
use App\Modules\Logs\Domain\Services\Files\LogFileFinder;
use App\Modules\Logs\Domain\Services\Index\LogIndexer;
use App\Modules\Logs\Entities\Log\LogLevelStatObject;
use App\Modules\Logs\Enums\LogTypeEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\Modules\Logs\LogsTempDirTrait;
use Tests\TestCase;

class FindLogErrorStatActionTest extends TestCase
{
    use LogsTempDirTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTempDir();

        mkdir($this->tempDir . '/logs/slogger');
        mkdir($this->tempDir . '/logs/nginx');

        config()->set('cache.mutex_store', 'array');
        config()->set('module-logs.errors.wait_for_lock_sec', 1);
        config()->set('module-logs.sources', [
            ['name' => 'Laravel', 'folder' => $this->tempDir . '/logs', 'pattern' => '*.log', 'type' => LogTypeEnum::Laravel],
            ['name' => 'Slogger', 'folder' => $this->tempDir . '/logs/slogger', 'pattern' => '*.log', 'type' => LogTypeEnum::Laravel],
            ['name' => 'Nginx', 'folder' => $this->tempDir . '/logs/nginx', 'pattern' => 'error*.log', 'type' => LogTypeEnum::NginxError],
        ]);

        $this->write('logs/laravel-2026-09-25.log', [
            ['10:00:00', 'ERROR', 'before the window'],
            ['10:00:10', 'ERROR', 'exactly at since, so outside'],
            ['10:00:11', 'INFO', 'not an error'],
            ['10:00:20', 'CRITICAL', 'critical one'],
            ['10:00:30', 'WARNING', 'a warning'],
            ['10:01:00', 'ALERT', 'at the end of the window'],
            ['10:01:01', 'EMERGENCY', 'after the window'],
        ]);

        $this->write('logs/slogger/slogger-2026-09-25.log', [
            ['10:00:40', 'ERROR', 'from slogger {"a":1}'],
            ['10:00:59', 'EMERGENCY', 'the latest {"context":true}'],
        ]);

        $this->write('logs/laravel-2026-09-24.log', [
            ['10:00:30', 'ERROR', 'a day old'],
        ], modifiedAt: gmmktime(23, 0, 0, 9, 24, 2026));

        file_put_contents(
            $this->tempDir . '/logs/nginx/error.log',
            "2026/09/25 10:00:30 [error] 1#1: *1 upstream failed\n"
        );
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();

        parent::tearDown();
    }

    public function testErrorsAndAboveOfLaravelLogsAfterSinceAndUpToUntilAreCounted(): void
    {
        $stat = $this->find('10:00:10', '10:01:00');

        $this->assertSame(4, $stat->count);
        $this->assertSame('at the end of the window', $stat->lastMessage);
    }

    public function testTheLastMessageIsTheLatestAcrossFilesWithoutItsContext(): void
    {
        $stat = $this->find('10:00:15', '10:00:59');

        $this->assertSame(3, $stat->count);
        $this->assertSame('the latest', $stat->lastMessage);
    }

    public function testAWindowWithoutErrorsSaysNothing(): void
    {
        $stat = $this->find('10:00:20', '10:00:39');

        $this->assertSame(0, $stat->count);
        $this->assertNull($stat->lastMessage);
    }

    public function testTimesOutOfOrderInAFileAreCountedOneByOne(): void
    {
        $this->write('logs/slogger/slogger-2026-09-25.log', [
            ['11:30:00', 'ERROR', 'written by a clock ahead'],
            ['10:00:40', 'ERROR', 'back in the window'],
            ['10:00:45', 'ERROR', 'still in the window'],
        ]);

        $stat = $this->find('10:00:10', '10:01:00');

        $this->assertSame(2 + 2, $stat->count);
        $this->assertSame('at the end of the window', $stat->lastMessage);
    }

    public function testAnIndexHeldByAnotherIndexerIsReadAsItIs(): void
    {
        foreach ($this->app->make(LogFileFinder::class)->findAll() as $file) {
            $this->app->make(LogIndexer::class)->ensureFresh($file);
        }

        $path = $this->tempDir . '/logs/laravel-2026-09-25.log';

        file_put_contents($path, "[2026-09-25 10:00:50] local.ERROR: not indexed yet \n", FILE_APPEND);

        touch($path, gmmktime(10, 5, 0, 9, 25, 2026));

        Cache::store('array')->lock('mutex:logs-index:' . sha1($path), 10)->get();

        $this->assertSame(4, $this->find('10:00:10', '10:01:00')->count);
    }

    private function find(string $since, string $until): LogLevelStatObject
    {
        return $this->app->make(FindLogErrorStatAction::class)->handle(
            new Carbon('2026-09-25 ' . $since, 'UTC'),
            new Carbon('2026-09-25 ' . $until, 'UTC')
        );
    }

    /**
     * @param list<array{0: string, 1: string, 2: string}> $entries
     */
    private function write(string $relativePath, array $entries, ?int $modifiedAt = null): void
    {
        $contents = '';

        foreach ($entries as [$time, $level, $message]) {
            $contents .= sprintf("[2026-09-25 %s] local.%s: %s \n", $time, $level, $message);
        }

        $path = sprintf('%s/%s', $this->tempDir, $relativePath);

        file_put_contents($path, $contents);

        touch($path, $modifiedAt ?? gmmktime(10, 5, 0, 9, 25, 2026));
    }
}
