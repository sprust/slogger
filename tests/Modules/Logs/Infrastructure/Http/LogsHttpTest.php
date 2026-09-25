<?php

namespace Tests\Modules\Logs\Infrastructure\Http;

use App\Modules\Auth\Infrastructure\Http\Middlewares\AuthMiddleware;
use App\Modules\Logs\Enums\LogTypeEnum;
use SLoggerLaravel\Middleware\HttpMiddleware as SLoggerHttpMiddleware;
use Tests\Modules\Logs\LogsTempDirTrait;
use Tests\TestCase;

class LogsHttpTest extends TestCase
{
    use LogsTempDirTrait;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTempDir();

        $this->withoutMiddleware([AuthMiddleware::class, SLoggerHttpMiddleware::class]);

        config()->set('cache.mutex_store', 'array');
        config()->set('module-logs.sources', [
            ['name' => 'Laravel', 'folder' => $this->tempDir . '/logs', 'pattern' => '*.log', 'type' => LogTypeEnum::Laravel],
        ]);

        $this->path = $this->tempDir . '/logs/laravel.log';

        file_put_contents(
            $this->path,
            "[2026-09-25 05:35:29] local.ERROR: boom {\"a\":\"б\"} \n[2026-09-25 05:35:30] local.INFO: ok \n"
        );
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();

        parent::tearDown();
    }

    public function testFilesAreListed(): void
    {
        $this->getJson('/admin-api/logs/files')
            ->assertOk()
            ->assertJsonPath('data.0.id', sha1($this->path))
            ->assertJsonPath('data.0.name', 'laravel.log')
            ->assertJsonPath('data.0.source', 'Laravel')
            ->assertJsonPath('data.0.type', 'laravel');
    }

    public function testEntriesAreFound(): void
    {
        $this->postJson('/admin-api/logs/entries', ['files' => [sha1($this->path)]])
            ->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.items.0.level', 'laravel.INFO')
            ->assertJsonPath('data.items.1.message', 'boom')
            ->assertJsonPath('data.items.1.context', '{"a":"б"}')
            ->assertJsonPath('data.items.1.fields.0.key', 'env')
            ->assertJsonPath('data.items.1.logged_at', '2026-09-25 05:35:29')
            ->assertJsonPath('data.level_counts.0.key', 'laravel.ERROR')
            ->assertJsonPath('data.older_cursor', null);
    }

    public function testTheRequestIsValidated(): void
    {
        $this->postJson('/admin-api/logs/entries', ['files' => [], 'direction' => 'sideways', 'per_page' => 100_000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['files', 'direction', 'per_page']);
    }

    public function testAMalformedCursorIsUnprocessable(): void
    {
        $this->postJson('/admin-api/logs/entries', ['files' => [sha1($this->path)], 'cursor' => 'garbage'])
            ->assertUnprocessable();
    }

    public function testAFileIsDownloaded(): void
    {
        $response = $this->get(sprintf('/admin-api/logs/files/%s/download', sha1($this->path)));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename=laravel.log');

        $this->assertSame(file_get_contents($this->path), $response->streamedContent());
    }

    public function testAFileOverTheLimitIsNotDownloaded(): void
    {
        config()->set('module-logs.download.max_bytes', 10);

        $this->get(sprintf('/admin-api/logs/files/%s/download', sha1($this->path)))->assertUnprocessable();
    }

    public function testAFileOutsideTheSourcesIsNotDownloaded(): void
    {
        $this->get(sprintf('/admin-api/logs/files/%s/download', sha1('/etc/passwd')))->assertNotFound();
    }
}
