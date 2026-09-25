<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Entry;

readonly class LogEntryTextObject
{
    public function __construct(
        public int $entryNo,
        public string $text
    ) {
    }
}
