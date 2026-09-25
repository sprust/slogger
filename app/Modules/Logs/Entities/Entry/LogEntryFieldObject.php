<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Entry;

readonly class LogEntryFieldObject
{
    public function __construct(
        public string $key,
        public ?string $value
    ) {
    }
}
