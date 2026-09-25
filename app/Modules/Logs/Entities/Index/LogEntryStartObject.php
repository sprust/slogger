<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Index;

readonly class LogEntryStartObject
{
    public function __construct(
        public int $offset,
        public ?int $loggedAt,
        public int $level
    ) {
    }
}
