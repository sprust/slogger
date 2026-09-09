<?php

declare(strict_types=1);

namespace App\Modules\Notification\Entities;

readonly class SendResultObject
{
    public function __construct(
        public bool $delivered,
        public bool $permanent = false,
        public ?string $error = null,
        public ?int $retryAfterSeconds = null
    ) {
    }
}
