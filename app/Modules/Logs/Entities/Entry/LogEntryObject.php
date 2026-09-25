<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Entry;

readonly class LogEntryObject
{
    public function __construct(
        public string $fileId,
        public int $entryNo,
        public int $loggedAt,
        public int $level,
        public string $text,
        public bool $truncated
    ) {
    }
}
