<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Services\Types;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Notification\Domain\Services\Senders\NotificationSenderInterface;
use App\Modules\Notification\Domain\Services\Senders\WebhookSender;
use App\Modules\Notification\Entities\ChannelTypeFieldObject;
use App\Modules\Notification\Entities\ChannelTypeObject;
use App\Modules\Notification\Entities\Settings\ChannelSettingsInterface;
use App\Modules\Notification\Entities\Settings\WebhookSettingsObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;

readonly class WebhookChannelType implements NotificationChannelTypeDefinitionInterface
{
    public function __construct(
        private WebhookSender $sender
    ) {
    }

    public function makeSettings(array $settings): ChannelSettingsInterface
    {
        return new WebhookSettingsObject(
            url: ArrayValueGetter::stringNull($settings, 'url') ?? '',
            token: ArrayValueGetter::stringNull($settings, 'token') ?? ''
        );
    }

    public function makeUpdatedSettings(array $submitted, ChannelSettingsInterface $stored): ChannelSettingsInterface
    {
        $settings = $this->makeSettings($submitted);

        if (!$settings instanceof WebhookSettingsObject || !$stored instanceof WebhookSettingsObject) {
            return $settings;
        }

        return new WebhookSettingsObject(
            url: $settings->url,
            token: $settings->token === '' ? $stored->token : $settings->token
        );
    }

    public function describe(): ChannelTypeObject
    {
        return new ChannelTypeObject(
            type: NotificationChannelTypeEnum::Webhook,
            title: 'Webhook',
            description: 'The message is posted as JSON to an address of your own.',
            fields: [
                new ChannelTypeFieldObject(
                    key: 'url',
                    title: 'Url',
                    description: 'Where to POST {channel, text, sent_at}.',
                    secret: false,
                    maxLength: 500
                ),
                new ChannelTypeFieldObject(
                    key: 'token',
                    title: 'Token',
                    description: 'Sent as the X-Slogger-Token header, so the receiver can tell it is us. '
                        . 'Optional. Stored encrypted and never shown back.',
                    secret: true,
                    required: false,
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
