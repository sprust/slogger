<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Index;

readonly class LogIndexBatchObject
{
    /**
     * @param list<LogFileIndexObject> $files
     * @param list<string>             $missingFileIds
     */
    public function __construct(
        public array $files,
        public array $missingFileIds,
        public bool $indexing,
        public int $indexedBytes,
        public int $totalBytes
    ) {
    }
}
