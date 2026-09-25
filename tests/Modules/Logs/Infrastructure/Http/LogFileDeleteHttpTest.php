<?php

namespace Tests\Modules\Logs\Infrastructure\Http;

use App\Modules\Auth\Infrastructure\Http\Middlewares\AuthMiddleware;
use App\Modules\Logs\Domain\Services\Files\LogFileFinder;
use App\Modules\Logs\Domain\Services\Index\LogIndexer;
use App\Modules\Logs\Enums\LogTypeEnum;
use SLoggerLaravel\Middleware\HttpMiddleware as SLoggerHttpMiddleware;
use Tests\Modules\Logs\LogsTempDirTrait;
use Tests\TestCase;

class LogFileDeleteHttpTest extends TestCase
{
    use LogsTempDirTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTempDir();

        mkdir($this->tempDir . '/logs/nginx');

        $this->withoutMiddleware([AuthMiddleware::class, SLoggerHttpMiddleware::class]);

        config()->set('cache.mutex_store', 'array');
        config()->set('module-logs.sources', [
            ['name' => 'Laravel', 'folder' => $this->tempDir . '/logs', 'pattern' => '*.log', 'type' => LogTypeEnum::Laravel, 'deletable' => true],
            ['name' => 'Nginx', 'folder' => $this->tempDir . '/logs/nginx', 'pattern' => 'error*.log', 'type' => LogTypeEnum::NginxError],
        ]);

        $this->write('logs/laravel-2026-09-24.log', gmmktime(23, 0, 0, 9, 24, 2026));
        $this->write('logs/laravel-2026-09-25.log', gmmktime(10, 0, 0, 9, 25, 2026));
        $this->write('logs/nginx/error.log', gmmktime(9, 0, 0, 9, 20, 2026));
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();

        parent::tearDown();
    }

    public function testOnlyAnOlderFileOfADeletableSourceCanBeDeleted(): void
    {
        $canDelete = [];

        foreach ($this->getJson('/admin-api/logs/files')->assertOk()->json('data') as $file) {
            $canDelete[$file['name']] = $file['can_delete'];
        }

        $this->assertEquals(
            ['laravel-2026-09-24.log' => true, 'laravel-2026-09-25.log' => false, 'error.log' => false],
            $canDelete
        );
    }

    public function testAFileIsDeletedWithItsIndex(): void
    {
        $path = $this->tempDir . '/logs/laravel-2026-09-24.log';

        foreach ($this->app->make(LogFileFinder::class)->findAll() as $file) {
            $this->app->make(LogIndexer::class)->ensureFresh($file);
        }

        $indexPath = sprintf('%s/%s', config('module-logs.index.path'), sha1($path));

        $this->assertDirectoryExists($indexPath);

        $this->deleteJson('/admin-api/logs/files/' . sha1($path))->assertOk();

        $this->assertFileDoesNotExist($path);
        // is_dir() above left the answer in PHP's stat cache.
        clearstatcache();

        $this->assertDirectoryDoesNotExist($indexPath);
    }

    public function testTheNewestFileIsKept(): void
    {
        $path = $this->tempDir . '/logs/laravel-2026-09-25.log';

        $this->deleteJson('/admin-api/logs/files/' . sha1($path))->assertUnprocessable();

        $this->assertFileExists($path);
    }

    public function testAFileOfASourceWithoutDeletingIsKept(): void
    {
        $path = $this->tempDir . '/logs/nginx/error.log';

        $this->deleteJson('/admin-api/logs/files/' . sha1($path))->assertUnprocessable();

        $this->assertFileExists($path);
    }

    public function testAFileOutsideTheSourcesIsNotFound(): void
    {
        $this->deleteJson('/admin-api/logs/files/' . sha1('/etc/passwd'))->assertNotFound();
    }

    private function write(string $relativePath, int $modifiedAt): void
    {
        $path = sprintf('%s/%s', $this->tempDir, $relativePath);

        file_put_contents($path, "[2026-09-25 05:35:29] local.ERROR: boom \n");

        touch($path, $modifiedAt);
    }
}
