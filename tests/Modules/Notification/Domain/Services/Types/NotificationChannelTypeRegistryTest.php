<?php

namespace Tests\Modules\Notification\Domain\Services\Types;

use App\Modules\Notification\Domain\Services\Senders\TelegramSender;
use App\Modules\Notification\Domain\Services\Types\NotificationChannelTypeRegistry;
use App\Modules\Notification\Domain\Services\Types\TelegramChannelType;
use App\Modules\Notification\Entities\Settings\TelegramSettingsObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use PHPUnit\Framework\TestCase;

class NotificationChannelTypeRegistryTest extends TestCase
{
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

    private function registry(): NotificationChannelTypeRegistry
    {
        return new NotificationChannelTypeRegistry(
            new TelegramChannelType($this->createMock(TelegramSender::class))
        );
    }
}
