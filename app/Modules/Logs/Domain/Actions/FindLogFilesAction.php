<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Actions;

use App\Modules\Logs\Domain\Services\Files\LogFileFinder;
use App\Modules\Logs\Entities\File\LogFileObject;

readonly class FindLogFilesAction
{
    public function __construct(
        private LogFileFinder $logFileFinder
    ) {
    }

    /**
     * @return list<LogFileObject>
     */
    public function handle(): array
    {
        $files = $this->logFileFinder->findAll();

        $sourceOrder = [];

        foreach ($files as $file) {
            $sourceOrder[$file->source] ??= count($sourceOrder);
        }

        usort(
            $files,
            static fn(LogFileObject $left, LogFileObject $right): int => [$sourceOrder[$left->source], $right->modifiedAtMs, $left->name]
                <=> [$sourceOrder[$right->source], $left->modifiedAtMs, $right->name]
        );

        return $files;
    }
}
