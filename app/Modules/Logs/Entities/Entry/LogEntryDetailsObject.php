<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Entry;

readonly class LogEntryDetailsObject
{
    /**
     * @param array<array-key, mixed>|null $context
     * @param array<string, string|null>   $fields
     */
    public function __construct(
        public string $message,
        public ?array $context,
        public array $fields
    ) {
    }
}
