<?php

declare(strict_types=1);

namespace App\Modules\Notification\Entities\Settings;

readonly class SlackSettingsObject implements ChannelSettingsInterface
{
    public function __construct(
        public string $webhookUrl = ''
    ) {
    }

    public function toArray(): array
    {
        return [
            'webhook_url' => $this->webhookUrl,
        ];
    }
}
