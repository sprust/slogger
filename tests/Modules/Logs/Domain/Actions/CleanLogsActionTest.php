<?php

namespace Tests\Modules\Logs\Domain\Actions;

use App\Modules\Logs\Domain\Actions\CleanLogsAction;
use App\Modules\Logs\Domain\Actions\IndexLogsAction;
use App\Modules\Logs\Enums\LogTypeEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\Modules\Logs\LogsTempDirTrait;
use Tests\TestCase;

class CleanLogsActionTest extends TestCase
{
    use LogsTempDirTrait;

    private Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTempDir();

        mkdir($this->tempDir . '/logs/nginx');

        config()->set('cache.mutex_store', 'array');
        config()->set('module-logs.sources', [
            ['name' => 'Laravel', 'folder' => $this->tempDir . '/logs', 'pattern' => '*.log', 'type' => LogTypeEnum::Laravel],
            ['name' => 'Nginx', 'folder' => $this->tempDir . '/logs/nginx', 'pattern' => 'access*.log', 'type' => LogTypeEnum::NginxAccess, 'keep_days' => 14],
            ['name' => 'Nginx', 'folder' => $this->tempDir . '/logs/nginx', 'pattern' => 'error*.log', 'type' => LogTypeEnum::NginxError],
        ]);

        $this->now = new Carbon('2026-09-25 12:00:00', 'UTC');

        $this->write('logs/laravel-2026-08-01.log', days: 55);
        $this->write('logs/nginx/access-2026-09-10.log', days: 15);
        $this->write('logs/nginx/access-2026-09-11.log', days: 13);
        $this->write('logs/nginx/error.log', days: 40);
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();

        parent::tearDown();
    }

    public function testOnlyFilesPastTheirSourceKeepDaysAreDeleted(): void
    {
        $result = $this->app->make(CleanLogsAction::class)->handle($this->now);

        $this->assertSame([$this->tempDir . '/logs/nginx/access-2026-09-10.log'], $result->deletedFiles);
        $this->assertFileDoesNotExist($this->tempDir . '/logs/nginx/access-2026-09-10.log');
        $this->assertFileExists($this->tempDir . '/logs/nginx/access-2026-09-11.log');
        $this->assertFileExists($this->tempDir . '/logs/laravel-2026-08-01.log');
        $this->assertFileExists($this->tempDir . '/logs/nginx/error.log');
    }

    public function testIndexesOfFilesThatAreGoneAreDeletedAndTheRestKept(): void
    {
        $batch = $this->app->make(IndexLogsAction::class)->handle();

        $this->assertCount(4, $batch->files);

        unlink($this->tempDir . '/logs/laravel-2026-08-01.log');

        $result = $this->app->make(CleanLogsAction::class)->handle($this->now);

        $this->assertSame(2, $result->deletedIndexes);
        $this->assertSame(0, $result->skippedIndexes);
        $this->assertDirectoryDoesNotExist($this->indexDir('logs/laravel-2026-08-01.log'));
        $this->assertDirectoryDoesNotExist($this->indexDir('logs/nginx/access-2026-09-10.log'));
        $this->assertDirectoryExists($this->indexDir('logs/nginx/access-2026-09-11.log'));
        $this->assertDirectoryExists($this->indexDir('logs/nginx/error.log'));
    }

    public function testAnIndexInUseIsLeftForTheNextRun(): void
    {
        $this->app->make(IndexLogsAction::class)->handle();

        unlink($this->tempDir . '/logs/laravel-2026-08-01.log');

        Cache::store('array')->lock('mutex:logs-index:' . sha1($this->tempDir . '/logs/laravel-2026-08-01.log'), 10)->get();

        $result = $this->app->make(CleanLogsAction::class)->handle($this->now);

        $this->assertSame(1, $result->deletedIndexes);
        $this->assertSame(1, $result->skippedIndexes);
        $this->assertDirectoryExists($this->indexDir('logs/laravel-2026-08-01.log'));
    }

    public function testIndexingTakesEveryFileOfEverySource(): void
    {
        $batch = $this->app->make(IndexLogsAction::class)->handle();

        $this->assertFalse($batch->indexing);
        $this->assertSame([], $batch->missingFileIds);
        $this->assertSame($batch->totalBytes, $batch->indexedBytes);
        $this->assertCount(4, $batch->files);
    }

    private function write(string $relativePath, int $days): void
    {
        $path = sprintf('%s/%s', $this->tempDir, $relativePath);

        file_put_contents($path, "[2026-08-01 00:00:00] local.INFO: line \n");

        touch($path, $this->now->copy()->subDays($days)->getTimestamp());
    }

    private function indexDir(string $relativePath): string
    {
        return sprintf('%s/index/%s', $this->tempDir, sha1(sprintf('%s/%s', $this->tempDir, $relativePath)));
    }
}
