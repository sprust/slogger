<?php

declare(strict_types=1);

namespace App\Modules\Notification\Entities;

readonly class ChannelTypeFieldObject
{
    public function __construct(
        public string $key,
        public string $title,
        public string $description,
        public bool $secret,
        public int $maxLength
    ) {
    }
}
