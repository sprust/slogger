<?php

declare(strict_types=1);

namespace App\Modules\Logs\Repositories;

use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Entities\File\LogFileStatObject;
use App\Modules\Logs\Entities\File\LogSourceObject;
use SConcur\Exceptions\Files\FileNotFoundException;
use SConcur\Features\Files\Files;

readonly class LogFileRepository
{
    /**
     * @return list<LogFileObject>
     */
    public function findInSource(LogSourceObject $source): array
    {
        try {
            $entries = Files::list(
                path: $source->folder,
                pattern: $source->pattern,
                withMetadata: true
            );
        } catch (FileNotFoundException) {
            return [];
        }

        $files = [];

        foreach ($entries as $entry) {
            if ($entry->isDirectory) {
                continue;
            }

            $files[] = new LogFileObject(
                id: sha1($entry->path),
                path: $entry->path,
                name: $entry->name,
                source: $source->name,
                folder: $source->folder,
                type: $source->type,
                sizeBytes: $entry->sizeBytes ?? 0,
                modifiedAtMs: $entry->modifiedAtMs ?? 0,
                keepDays: $source->keepDays,
                deletable: $source->deletable
            );
        }

        return $files;
    }

    public function stat(string $path): LogFileStatObject
    {
        $stat = Files::stat(path: $path);

        return new LogFileStatObject(
            exists: $stat->exists && $stat->isFile,
            sizeBytes: $stat->sizeBytes,
            modifiedAtMs: $stat->modifiedAtMs
        );
    }

    public function read(string $path, int $offset, int $length): string
    {
        if ($length <= 0) {
            return '';
        }

        return Files::read(
            path: $path,
            offsetBytes: $offset,
            lengthBytes: $length,
            maxReadBytes: 0
        );
    }

    public function delete(string $path): bool
    {
        return Files::delete(path: $path, missingOk: true);
    }

    /**
     * @return iterable<string>
     */
    public function readChunks(string $path): iterable
    {
        yield from Files::readChunks(path: $path, bufferSizeBytes: 256 * 1024);
    }
}
