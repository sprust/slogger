<?php

declare(strict_types=1);

namespace App\Modules\Notification\Entities;

use App\Modules\Notification\Entities\Settings\ChannelSettingsInterface;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use Illuminate\Support\Carbon;

readonly class ChannelObject
{
    public function __construct(
        public int $id,
        public string $name,
        public NotificationChannelTypeEnum $type,
        public bool $enabled,
        public bool $onOpened,
        public bool $onEvent,
        public bool $onClosed,
        public ChannelSettingsInterface $settings,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }
}
