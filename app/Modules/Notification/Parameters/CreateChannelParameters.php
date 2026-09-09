<?php

declare(strict_types=1);

namespace App\Modules\Notification\Parameters;

use App\Modules\Notification\Enums\NotificationChannelTypeEnum;

readonly class CreateChannelParameters
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        public string $name,
        public NotificationChannelTypeEnum $type,
        public bool $enabled,
        public bool $onOpened,
        public bool $onEvent,
        public bool $onClosed,
        public array $settings
    ) {
    }
}
