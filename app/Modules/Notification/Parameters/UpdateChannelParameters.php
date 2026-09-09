<?php

declare(strict_types=1);

namespace App\Modules\Notification\Parameters;

readonly class UpdateChannelParameters
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        public int $id,
        public string $name,
        public bool $enabled,
        public bool $onOpened,
        public bool $onEvent,
        public bool $onClosed,
        public array $settings
    ) {
    }
}
