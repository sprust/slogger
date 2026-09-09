<?php

declare(strict_types=1);

namespace App\Modules\Notification\Entities\Settings;

readonly class TelegramSettingsObject implements ChannelSettingsInterface
{
    public function __construct(
        public string $botToken = '',
        public string $chatId = ''
    ) {
    }

    public function toArray(): array
    {
        return [
            'bot_token' => $this->botToken,
            'chat_id'   => $this->chatId,
        ];
    }
}
