<?php

declare(strict_types=1);

namespace App\Modules\Notification\Entities\Settings;

readonly class WebhookSettingsObject implements ChannelSettingsInterface
{
    public function __construct(
        public string $url = '',
        public string $token = ''
    ) {
    }

    public function toArray(): array
    {
        return [
            'url'   => $this->url,
            'token' => $this->token,
        ];
    }
}
