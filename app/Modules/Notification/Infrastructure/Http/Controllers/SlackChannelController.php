<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Controllers;

use App\Modules\Notification\Domain\Exceptions\NotificationChannelNotFoundException;
use App\Modules\Notification\Entities\Settings\SlackSettingsObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use App\Modules\Notification\Infrastructure\Http\Requests\CreateSlackChannelRequest;
use App\Modules\Notification\Infrastructure\Http\Requests\UpdateSlackChannelRequest;
use App\Modules\Notification\Infrastructure\Http\Resources\ChannelResource;
use App\Modules\Notification\Infrastructure\Http\Resources\SlackChannelSettingsResource;

readonly class SlackChannelController extends AbstractChannelTypeController
{
    public function show(int $id): SlackChannelSettingsResource
    {
        $settings = $this->channelSettings(
            type: NotificationChannelTypeEnum::Slack,
            id: $id,
        );

        if (!$settings instanceof SlackSettingsObject) {
            throw new NotificationChannelNotFoundException($id);
        }

        return new SlackChannelSettingsResource(
            id: $id,
            resource: $settings,
        );
    }

    public function create(CreateSlackChannelRequest $request): ChannelResource
    {
        return $this->createChannel(
            type: NotificationChannelTypeEnum::Slack,
            validated: $request->validated(),
        );
    }

    public function update(int $id, UpdateSlackChannelRequest $request): void
    {
        $this->updateChannel(
            type: NotificationChannelTypeEnum::Slack,
            id: $id,
            validated: $request->validated(),
        );
    }
}
