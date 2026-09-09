<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Actions\Mutations;

use App\Modules\Notification\Domain\Services\ChannelFactory;
use App\Modules\Notification\Domain\Services\Types\NotificationChannelTypeRegistry;
use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Parameters\CreateChannelParameters;
use App\Modules\Notification\Repositories\ChannelRepository;
use LogicException;

readonly class CreateChannelAction
{
    public function __construct(
        private ChannelRepository $channelRepository,
        private NotificationChannelTypeRegistry $types,
        private ChannelFactory $channelFactory
    ) {
    }

    public function handle(CreateChannelParameters $parameters): ChannelObject
    {
        $settings = $this->types->for($parameters->type)->makeSettings($parameters->settings);

        $channel = $this->channelFactory->make(
            $this->channelRepository->create(
                name: $parameters->name,
                type: $parameters->type->value,
                enabled: $parameters->enabled,
                onOpened: $parameters->onOpened,
                onEvent: $parameters->onEvent,
                onClosed: $parameters->onClosed,
                settings: $settings->toArray()
            )
        );

        if (is_null($channel)) {
            throw new LogicException(
                sprintf(
                    "Notification channel of type [%s] could not be read back",
                    $parameters->type->value
                )
            );
        }

        return $channel;
    }
}
