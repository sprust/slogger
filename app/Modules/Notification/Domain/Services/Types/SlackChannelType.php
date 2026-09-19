<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Services\Types;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Notification\Domain\Services\Senders\NotificationSenderInterface;
use App\Modules\Notification\Domain\Services\Senders\SlackSender;
use App\Modules\Notification\Entities\ChannelTypeFieldObject;
use App\Modules\Notification\Entities\ChannelTypeObject;
use App\Modules\Notification\Entities\Settings\ChannelSettingsInterface;
use App\Modules\Notification\Entities\Settings\SlackSettingsObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;

readonly class SlackChannelType implements NotificationChannelTypeDefinitionInterface
{
    public function __construct(
        private SlackSender $sender
    ) {
    }

    public function makeSettings(array $settings): ChannelSettingsInterface
    {
        return new SlackSettingsObject(
            webhookUrl: ArrayValueGetter::stringNull($settings, 'webhook_url') ?? ''
        );
    }

    public function makeUpdatedSettings(array $submitted, ChannelSettingsInterface $stored): ChannelSettingsInterface
    {
        $settings = $this->makeSettings($submitted);

        if (!$settings instanceof SlackSettingsObject || !$stored instanceof SlackSettingsObject) {
            return $settings;
        }

        return new SlackSettingsObject(
            webhookUrl: $settings->webhookUrl === '' ? $stored->webhookUrl : $settings->webhookUrl
        );
    }

    public function describe(): ChannelTypeObject
    {
        return new ChannelTypeObject(
            type: NotificationChannelTypeEnum::Slack,
            title: 'Slack',
            description: 'An incoming webhook posts into the channel it was created for.',
            fields: [
                new ChannelTypeFieldObject(
                    key: 'webhook_url',
                    title: 'Webhook url',
                    description: 'The hooks.slack.com address of the incoming webhook. '
                        . 'It is the credential: stored encrypted and never shown back.',
                    secret: true,
                    maxLength: 500
                ),
            ]
        );
    }

    public function sender(): NotificationSenderInterface
    {
        return $this->sender;
    }
}
