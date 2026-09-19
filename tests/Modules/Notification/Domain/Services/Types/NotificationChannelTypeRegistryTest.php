<?php

namespace Tests\Modules\Notification\Domain\Services\Types;

use App\Modules\Notification\Domain\Services\Types\NotificationChannelTypeRegistry;
use App\Modules\Notification\Entities\Settings\SlackSettingsObject;
use App\Modules\Notification\Entities\Settings\TelegramSettingsObject;
use App\Modules\Notification\Entities\Settings\WebhookSettingsObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Notification\NotificationFactoryTrait;

class NotificationChannelTypeRegistryTest extends TestCase
{
    use NotificationFactoryTrait;

    public function testEveryTypeHasADefinition(): void
    {
        $registry = $this->registry();

        foreach (NotificationChannelTypeEnum::cases() as $type) {
            $this->assertSame($type, $registry->for($type)->describe()->type);
        }
    }

    public function testSettingsSurviveTheRoundTrip(): void
    {
        $settings = new TelegramSettingsObject(botToken: '123:abc', chatId: '-100500');

        $this->assertEquals(
            $settings,
            $this->registry()->for(NotificationChannelTypeEnum::Telegram)->makeSettings($settings->toArray())
        );
    }

    public function testEveryTypeDescribesItsFields(): void
    {
        $registry = $this->registry();

        foreach (NotificationChannelTypeEnum::cases() as $type) {
            $this->assertNotEmpty($registry->for($type)->describe()->fields, $type->value);
        }
    }

    public function testAnEmptySecretOnAnEditKeepsTheStoredOne(): void
    {
        $settings = $this->registry()->for(NotificationChannelTypeEnum::Telegram)->makeUpdatedSettings(
            submitted: ['bot_token' => '', 'chat_id' => '@new'],
            stored: new TelegramSettingsObject(botToken: '123:abc', chatId: '@old')
        );

        $this->assertInstanceOf(TelegramSettingsObject::class, $settings);
        $this->assertSame('123:abc', $settings->botToken);
        $this->assertSame('@new', $settings->chatId);
    }

    public function testANewSecretOnAnEditReplacesTheStoredOne(): void
    {
        $settings = $this->registry()->for(NotificationChannelTypeEnum::Telegram)->makeUpdatedSettings(
            submitted: ['bot_token' => '456:def', 'chat_id' => '@old'],
            stored: new TelegramSettingsObject(botToken: '123:abc', chatId: '@old')
        );

        $this->assertInstanceOf(TelegramSettingsObject::class, $settings);
        $this->assertSame('456:def', $settings->botToken);
    }

    public function testAnEmptySlackUrlOnAnEditKeepsTheStoredOne(): void
    {
        $settings = $this->registry()->for(NotificationChannelTypeEnum::Slack)->makeUpdatedSettings(
            submitted: ['webhook_url' => ''],
            stored: new SlackSettingsObject(webhookUrl: 'https://hooks.slack.com/services/T1/B2/abc')
        );

        $this->assertInstanceOf(SlackSettingsObject::class, $settings);
        $this->assertSame('https://hooks.slack.com/services/T1/B2/abc', $settings->webhookUrl);
    }

    public function testAWebhookKeepsItsTokenWhileItsUrlIsMoved(): void
    {
        $settings = $this->registry()->for(NotificationChannelTypeEnum::Webhook)->makeUpdatedSettings(
            submitted: ['url' => 'https://ops.example.com/new', 'token' => ''],
            stored: new WebhookSettingsObject(url: 'https://ops.example.com/old', token: 'secret-token')
        );

        $this->assertInstanceOf(WebhookSettingsObject::class, $settings);
        $this->assertSame('https://ops.example.com/new', $settings->url);
        $this->assertSame('secret-token', $settings->token);
    }

    private function registry(): NotificationChannelTypeRegistry
    {
        return $this->channelTypes();
    }
}
