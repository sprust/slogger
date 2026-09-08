<?php

declare(strict_types=1);

namespace App\Modules\Notification\Entities;

use App\Modules\Notification\Enums\NotificationChannelTypeEnum;

readonly class ChannelTypeObject
{
    /**
     * @param ChannelTypeFieldObject[] $fields
     */
    public function __construct(
        public NotificationChannelTypeEnum $type,
        public string $title,
        public string $description,
        public array $fields
    ) {
    }
}
