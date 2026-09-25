<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Index;

readonly class LogCleanResultObject
{
    /**
     * @param list<string> $deletedFiles
     */
    public function __construct(
        public array $deletedFiles,
        public int $deletedIndexes,
        public int $skippedIndexes
    ) {
    }
}
