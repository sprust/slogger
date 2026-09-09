<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Actions\Mutations;

use App\Modules\Notification\Domain\Actions\Queries\FindChannelAction;
use App\Modules\Notification\Domain\Exceptions\NotificationChannelNotFoundException;
use App\Modules\Notification\Domain\Services\Types\NotificationChannelTypeRegistry;
use App\Modules\Notification\Parameters\UpdateChannelParameters;
use App\Modules\Notification\Repositories\ChannelRepository;

readonly class UpdateChannelAction
{
    public function __construct(
        private ChannelRepository $channelRepository,
        private NotificationChannelTypeRegistry $types,
        private FindChannelAction $findChannelAction
    ) {
    }

    /**
     * @throws NotificationChannelNotFoundException
     */
    public function handle(UpdateChannelParameters $parameters): void
    {
        $channel = $this->findChannelAction->handle($parameters->id);

        if (is_null($channel)) {
            throw new NotificationChannelNotFoundException($parameters->id);
        }

        $settings = $this->types->for($channel->type)->makeUpdatedSettings(
            submitted: $parameters->settings,
            stored: $channel->settings
        );

        $this->channelRepository->update(
            id: $parameters->id,
            name: $parameters->name,
            enabled: $parameters->enabled,
            onOpened: $parameters->onOpened,
            onEvent: $parameters->onEvent,
            onClosed: $parameters->onClosed,
            settings: $settings->toArray()
        );
    }
}
