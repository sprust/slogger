<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Controllers;

use App\Modules\Notification\Domain\Exceptions\NotificationChannelNotFoundException;
use App\Modules\Notification\Entities\Settings\WebhookSettingsObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use App\Modules\Notification\Infrastructure\Http\Requests\CreateWebhookChannelRequest;
use App\Modules\Notification\Infrastructure\Http\Requests\UpdateWebhookChannelRequest;
use App\Modules\Notification\Infrastructure\Http\Resources\ChannelResource;
use App\Modules\Notification\Infrastructure\Http\Resources\WebhookChannelSettingsResource;

readonly class WebhookChannelController extends AbstractChannelTypeController
{
    public function show(int $id): WebhookChannelSettingsResource
    {
        $settings = $this->channelSettings(
            type: NotificationChannelTypeEnum::Webhook,
            id: $id,
        );

        if (!$settings instanceof WebhookSettingsObject) {
            throw new NotificationChannelNotFoundException($id);
        }

        return new WebhookChannelSettingsResource(
            id: $id,
            resource: $settings,
        );
    }

    public function create(CreateWebhookChannelRequest $request): ChannelResource
    {
        return $this->createChannel(
            type: NotificationChannelTypeEnum::Webhook,
            validated: $request->validated(),
        );
    }

    public function update(int $id, UpdateWebhookChannelRequest $request): void
    {
        $this->updateChannel(
            type: NotificationChannelTypeEnum::Webhook,
            id: $id,
            validated: $request->validated(),
        );
    }
}
