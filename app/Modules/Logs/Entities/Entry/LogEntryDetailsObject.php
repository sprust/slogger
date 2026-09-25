<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Entry;

readonly class LogEntryDetailsObject
{
    /**
     * @param list<LogEntryFieldObject> $fields
     */
    public function __construct(
        public string $message,
        public ?string $context,
        public array $fields
    ) {
    }
}
