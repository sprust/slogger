<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Services\Types;

use App\Modules\Notification\Entities\ChannelTypeObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;

readonly class NotificationChannelTypeRegistry
{
    public function __construct(
        private TelegramChannelType $telegram
    ) {
    }

    public function for(NotificationChannelTypeEnum $type): NotificationChannelTypeDefinitionInterface
    {
        return match ($type) {
            NotificationChannelTypeEnum::Telegram => $this->telegram,
        };
    }

    /**
     * @return ChannelTypeObject[]
     */
    public function describeAll(): array
    {
        return array_map(
            fn(NotificationChannelTypeEnum $type): ChannelTypeObject => $this->for($type)->describe(),
            NotificationChannelTypeEnum::cases()
        );
    }
}
