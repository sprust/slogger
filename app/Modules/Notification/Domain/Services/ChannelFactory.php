<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Services;

use App\Modules\Notification\Domain\Services\Types\NotificationChannelTypeRegistry;
use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use App\Modules\Notification\Repositories\Dto\ChannelDto;
use Psr\Log\LoggerInterface;

readonly class ChannelFactory
{
    public function __construct(
        private NotificationChannelTypeRegistry $types,
        private LoggerInterface $logger
    ) {
    }

    public function make(ChannelDto $dto): ?ChannelObject
    {
        $type = NotificationChannelTypeEnum::tryFrom($dto->type);

        if (is_null($type)) {
            $this->logger->warning(
                sprintf(
                    "Notification channel [%s] has an unknown type [%s] and was skipped",
                    $dto->id,
                    $dto->type
                )
            );

            return null;
        }

        return new ChannelObject(
            id: $dto->id,
            name: $dto->name,
            type: $type,
            enabled: $dto->enabled,
            onOpened: $dto->onOpened,
            onEvent: $dto->onEvent,
            onClosed: $dto->onClosed,
            settings: $this->types->for($type)->makeSettings($dto->settings),
            createdAt: $dto->createdAt,
            updatedAt: $dto->updatedAt
        );
    }
}
