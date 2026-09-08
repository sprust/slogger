<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Controllers;

use App\Modules\Notification\Entities\Settings\TelegramSettingsObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use App\Modules\Notification\Infrastructure\Http\Requests\CreateTelegramChannelRequest;
use App\Modules\Notification\Infrastructure\Http\Requests\UpdateTelegramChannelRequest;
use App\Modules\Notification\Infrastructure\Http\Resources\ChannelResource;
use App\Modules\Notification\Infrastructure\Http\Resources\TelegramChannelSettingsResource;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class TelegramChannelController extends AbstractChannelTypeController
{
    public function show(int $id): TelegramChannelSettingsResource
    {
        $settings = $this->channelSettings(
            type: NotificationChannelTypeEnum::Telegram,
            id: $id
        );

        if (!$settings instanceof TelegramSettingsObject) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "Notification channel [$id] not found");
        }

        return new TelegramChannelSettingsResource($id, $settings);
    }

    public function create(CreateTelegramChannelRequest $request): ChannelResource
    {
        return $this->createChannel(NotificationChannelTypeEnum::Telegram, $request->validated());
    }

    public function update(int $id, UpdateTelegramChannelRequest $request): void
    {
        $this->updateChannel(
            type: NotificationChannelTypeEnum::Telegram,
            id: $id,
            validated: $request->validated()
        );
    }
}
