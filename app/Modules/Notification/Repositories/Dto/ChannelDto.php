<?php

declare(strict_types=1);

namespace App\Modules\Notification\Repositories\Dto;

use Illuminate\Support\Carbon;

readonly class ChannelDto
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $type,
        public bool $enabled,
        public bool $onOpened,
        public bool $onEvent,
        public bool $onClosed,
        public array $settings,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }
}
