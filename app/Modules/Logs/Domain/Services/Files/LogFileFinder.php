<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Files;

use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Entities\File\LogSourceObject;
use App\Modules\Logs\Enums\LogTypeEnum;
use App\Modules\Logs\Repositories\LogFileRepository;

readonly class LogFileFinder
{
    public function __construct(
        private LogFileRepository $logFileRepository
    ) {
    }

    /**
     * @return list<LogFileObject>
     */
    public function findAll(): array
    {
        $files = [];

        foreach ($this->makeSources() as $source) {
            foreach ($this->logFileRepository->findInSource($source) as $file) {
                $files[$file->id] ??= $file;
            }
        }

        return array_values($files);
    }

    public function findById(string $id): ?LogFileObject
    {
        foreach ($this->findAll() as $file) {
            if ($file->id === $id) {
                return $file;
            }
        }

        return null;
    }

    /**
     * @return list<LogSourceObject>
     */
    private function makeSources(): array
    {
        $sources = [];

        foreach ((array) config('module-logs.sources') as $source) {
            $sources[] = new LogSourceObject(
                folder: rtrim((string) $source['folder'], '/'),
                pattern: (string) $source['pattern'],
                type: LogTypeEnum::from((string) $source['type'])
            );
        }

        return $sources;
    }
}
