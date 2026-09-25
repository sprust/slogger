<?php

namespace Tests\Modules\Logs\Domain\Services\Files;

use App\Modules\Logs\Domain\Services\Files\LogFileFinder;
use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Enums\LogTypeEnum;
use Tests\Modules\Logs\LogsTempDirTrait;
use Tests\TestCase;

class LogFileFinderTest extends TestCase
{
    use LogsTempDirTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTempDir();

        mkdir($this->tempDir . '/logs/nginx');

        config()->set('module-logs.sources', [
            ['name' => 'Laravel', 'folder' => $this->tempDir . '/logs', 'pattern' => '*.log', 'type' => LogTypeEnum::Laravel],
            ['name' => 'Nginx', 'folder' => $this->tempDir . '/logs/nginx', 'pattern' => 'access*.log', 'type' => LogTypeEnum::NginxAccess],
            ['name' => 'Nginx', 'folder' => $this->tempDir . '/logs/nginx', 'pattern' => 'error*.log', 'type' => 'nginx_error'],
            ['name' => 'Missing', 'folder' => $this->tempDir . '/missing', 'pattern' => '*.log', 'type' => LogTypeEnum::Laravel],
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();

        parent::tearDown();
    }

    public function testFilesAreFoundBySourceWithTheirType(): void
    {
        file_put_contents($this->tempDir . '/logs/laravel-2026-09-25.log', 'abc');
        file_put_contents($this->tempDir . '/logs/notes.txt', 'x');
        file_put_contents($this->tempDir . '/logs/nginx/access-2026-09-25.log', 'a');
        file_put_contents($this->tempDir . '/logs/nginx/error.log', 'e');

        $files = $this->app->make(LogFileFinder::class)->findAll();

        $this->assertSame(
            [
                ['laravel-2026-09-25.log', 'Laravel', LogTypeEnum::Laravel, 3],
                ['access-2026-09-25.log', 'Nginx', LogTypeEnum::NginxAccess, 1],
                ['error.log', 'Nginx', LogTypeEnum::NginxError, 1],
            ],
            array_map(
                static fn(LogFileObject $file): array => [$file->name, $file->source, $file->type, $file->sizeBytes],
                $files
            )
        );
        $this->assertSame(sha1($this->tempDir . '/logs/nginx/error.log'), $files[2]->id);
        $this->assertSame($this->tempDir . '/logs/nginx', $files[2]->folder);
    }

    public function testAFileIsFoundByIdOnlyAmongTheSources(): void
    {
        file_put_contents($this->tempDir . '/logs/laravel.log', 'abc');

        $finder = $this->app->make(LogFileFinder::class);

        $this->assertSame('laravel.log', $finder->findById(sha1($this->tempDir . '/logs/laravel.log'))?->name);
        $this->assertNull($finder->findById(sha1('/etc/passwd')));
    }
}
