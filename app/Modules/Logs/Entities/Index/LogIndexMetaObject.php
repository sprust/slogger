<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Index;

use App\Modules\Logs\Enums\LogTypeEnum;

readonly class LogIndexMetaObject
{
    /**
     * @param array<int, int> $levelCounts
     */
    public function __construct(
        public int $version,
        public string $path,
        public LogTypeEnum $type,
        public int $indexedBytes,
        public bool $lastEntryOpen,
        public int $headLength,
        public string $headHash,
        public int $entriesCount,
        public array $levelCounts
    ) {
    }
}
