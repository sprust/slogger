<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Entry;

use App\Modules\Logs\Enums\LogTypeEnum;

readonly class LogEntryViewObject
{
    public function __construct(
        public string $fileId,
        public LogTypeEnum $type,
        public int $entryNo,
        public int $loggedAt,
        public string $levelKey,
        public LogEntryDetailsObject $details,
        public string $text,
        public bool $truncated
    ) {
    }
}
