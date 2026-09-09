<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Services\Types;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Notification\Domain\Services\Senders\NotificationSenderInterface;
use App\Modules\Notification\Domain\Services\Senders\TelegramSender;
use App\Modules\Notification\Entities\ChannelTypeFieldObject;
use App\Modules\Notification\Entities\ChannelTypeObject;
use App\Modules\Notification\Entities\Settings\ChannelSettingsInterface;
use App\Modules\Notification\Entities\Settings\TelegramSettingsObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;

readonly class TelegramChannelType implements NotificationChannelTypeDefinitionInterface
{
    public function __construct(
        private TelegramSender $sender
    ) {
    }

    public function makeSettings(array $settings): ChannelSettingsInterface
    {
        return new TelegramSettingsObject(
            botToken: ArrayValueGetter::stringNull($settings, 'bot_token') ?? '',
            chatId: ArrayValueGetter::stringNull($settings, 'chat_id') ?? ''
        );
    }

    public function makeUpdatedSettings(array $submitted, ChannelSettingsInterface $stored): ChannelSettingsInterface
    {
        $settings = $this->makeSettings($submitted);

        if (!$settings instanceof TelegramSettingsObject || !$stored instanceof TelegramSettingsObject) {
            return $settings;
        }

        return new TelegramSettingsObject(
            botToken: $settings->botToken === '' ? $stored->botToken : $settings->botToken,
            chatId: $settings->chatId
        );
    }

    public function describe(): ChannelTypeObject
    {
        return new ChannelTypeObject(
            type: NotificationChannelTypeEnum::Telegram,
            title: 'Telegram',
            description: 'A bot posts into a chat, a group or a channel.',
            fields: [
                new ChannelTypeFieldObject(
                    key: 'bot_token',
                    title: 'Bot token',
                    description: 'What BotFather gave you. Stored encrypted and never shown back.',
                    secret: true,
                    maxLength: 255
                ),
                new ChannelTypeFieldObject(
                    key: 'chat_id',
                    title: 'Chat id',
                    description: 'A number for a chat, a negative one for a group, or @name for a public channel.',
                    secret: false,
                    maxLength: 255
                ),
            ]
        );
    }

    public function sender(): NotificationSenderInterface
    {
        return $this->sender;
    }
}
