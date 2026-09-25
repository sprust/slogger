<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Entry;

readonly class LogEntriesPageObject
{
    /**
     * @param list<LogEntryViewObject>     $items
     * @param list<LogLevelKeyCountObject> $levelCounts
     * @param list<string>                 $restartedFileIds
     * @param list<string>                 $missingFileIds
     */
    public function __construct(
        public array $items,
        public array $levelCounts,
        public int $total,
        public int $scanned,
        public bool $indexing,
        public int $indexedBytes,
        public int $totalBytes,
        public array $restartedFileIds,
        public array $missingFileIds,
        public ?string $olderCursor,
        public ?string $newerCursor
    ) {
    }
}
