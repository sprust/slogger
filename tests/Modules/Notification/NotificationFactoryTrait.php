<?php

namespace Tests\Modules\Notification;

use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Entities\NotificationObject;
use App\Modules\Notification\Entities\Settings\TelegramSettingsObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use App\Modules\Notification\Enums\NotificationKindEnum;
use Illuminate\Support\Carbon;

trait NotificationFactoryTrait
{
    private function channel(int $id = 1, bool $enabled = true): ChannelObject
    {
        $now = Carbon::parse('2026-09-08 12:00:00');

        return new ChannelObject(
            id: $id,
            name: 'ops chat',
            type: NotificationChannelTypeEnum::Telegram,
            enabled: $enabled,
            onOpened: true,
            onEvent: false,
            onClosed: true,
            settings: new TelegramSettingsObject(botToken: '123:abc', chatId: '-100500'),
            createdAt: $now,
            updatedAt: $now
        );
    }

    private function notification(
        string $id = '68be1f000000000000000001',
        int $channelId = 1,
        ?Carbon $sentAt = null
    ): NotificationObject {
        return new NotificationObject(
            id: $id,
            channelId: $channelId,
            watcherId: 7,
            incidentId: '68be1f000000000000000009',
            kind: NotificationKindEnum::Opened,
            text: 'a watcher went off',
            sentAt: $sentAt,
            error: null,
            createdAt: Carbon::parse('2026-09-08 12:00:00')
        );
    }
}
