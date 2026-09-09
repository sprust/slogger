<?php

namespace Tests\Modules\Notification\Domain\Services;

use App\Modules\Notification\Domain\Services\ChannelFactory;
use App\Modules\Notification\Domain\Services\Senders\TelegramSender;
use App\Modules\Notification\Domain\Services\Types\NotificationChannelTypeRegistry;
use App\Modules\Notification\Domain\Services\Types\TelegramChannelType;
use App\Modules\Notification\Entities\Settings\TelegramSettingsObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use App\Modules\Notification\Repositories\Dto\ChannelDto;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ChannelFactoryTest extends TestCase
{
    public function testTheStoredTypeDecidesHowTheSettingsAreRead(): void
    {
        $channel = $this->factory()->make(
            $this->dto(settings: ['bot_token' => '123:abc', 'chat_id' => '@ops'])
        );

        $this->assertSame(NotificationChannelTypeEnum::Telegram, $channel->type);
        $this->assertInstanceOf(TelegramSettingsObject::class, $channel->settings);
        $this->assertSame('123:abc', $channel->settings->botToken);
        $this->assertSame('@ops', $channel->settings->chatId);
    }

    public function testARowOfAnUnknownTypeIsSkippedRatherThanFatal(): void
    {
        $this->assertNull($this->factory()->make($this->dto(type: 'carrierPigeon')));
    }

    public function testAnEmptyColumnLoadsOnDefaults(): void
    {
        $channel = $this->factory()->make($this->dto(settings: []));

        $this->assertInstanceOf(TelegramSettingsObject::class, $channel->settings);
        $this->assertSame('', $channel->settings->botToken);
    }

    public function testTheSwitchesComeBackAsTheyWereStored(): void
    {
        $channel = $this->factory()->make($this->dto(onEvent: true));

        $this->assertTrue($channel->onOpened);
        $this->assertTrue($channel->onEvent);
        $this->assertTrue($channel->onClosed);
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function dto(
        string $type = 'telegram',
        array $settings = [],
        bool $enabled = true,
        bool $onEvent = false
    ): ChannelDto {
        $now = Carbon::parse('2026-09-08 12:00:00');

        return new ChannelDto(
            id: 1,
            name: 'ops chat',
            type: $type,
            enabled: $enabled,
            onOpened: true,
            onEvent: $onEvent,
            onClosed: true,
            settings: $settings,
            createdAt: $now,
            updatedAt: $now
        );
    }

    private function factory(): ChannelFactory
    {
        return new ChannelFactory(
            types: new NotificationChannelTypeRegistry(
                new TelegramChannelType($this->createMock(TelegramSender::class))
            ),
            logger: $this->createMock(LoggerInterface::class)
        );
    }
}
