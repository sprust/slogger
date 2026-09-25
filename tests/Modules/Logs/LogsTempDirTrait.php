<?php

namespace Tests\Modules\Logs;

use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Enums\LogTypeEnum;
use SConcur\Features\Files\Files;

trait LogsTempDirTrait
{
    protected string $tempDir;

    protected function makeTempDir(): void
    {
        $this->tempDir = sprintf('%s/slogger-logs-test-%s', sys_get_temp_dir(), bin2hex(random_bytes(6)));

        mkdir($this->tempDir . '/logs', 0755, true);
        mkdir($this->tempDir . '/index', 0755, true);

        config()->set('module-logs.index.path', $this->tempDir . '/index');
    }

    protected function removeTempDir(): void
    {
        Files::removeDirectory(path: $this->tempDir, recursive: true, missingOk: true);
    }

    protected function writeLogFile(
        string $name,
        string $contents,
        LogTypeEnum $type = LogTypeEnum::Laravel,
        bool $append = false
    ): LogFileObject {
        $path = sprintf('%s/logs/%s', $this->tempDir, $name);

        file_put_contents($path, $contents, $append ? FILE_APPEND : 0);

        clearstatcache(true, $path);

        return new LogFileObject(
            id: sha1($path),
            path: $path,
            name: $name,
            folder: $this->tempDir . '/logs',
            type: $type,
            sizeBytes: (int) filesize($path),
            modifiedAtMs: (int) filemtime($path) * 1000
        );
    }
}
