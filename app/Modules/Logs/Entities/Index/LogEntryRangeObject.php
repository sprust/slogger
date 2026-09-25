<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Index;

readonly class LogEntryRangeObject
{
    public function __construct(
        public int $lowEntryNo,
        public int $highEntryNo
    ) {
    }
}
