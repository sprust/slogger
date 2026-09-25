<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Index;

readonly class LogIndexRecordObject
{
    public function __construct(
        public int $entryNo,
        public int $offset,
        public int $length,
        public int $loggedAt,
        public int $level
    ) {
    }
}
