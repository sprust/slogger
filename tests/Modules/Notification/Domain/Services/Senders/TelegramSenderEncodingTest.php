<?php

namespace Tests\Modules\Notification\Domain\Services\Senders;

use App\Modules\Notification\Domain\Services\Senders\TelegramSender;
use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Entities\Settings\TelegramSettingsObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

class TelegramSenderEncodingTest extends TestCase
{
    public function testTextThatCannotBeEncodedIsRefusedRatherThanThrown(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->never())->method('sendRequest');

        $result = new TelegramSender($client)->send($this->channel(), "\xB1\x31");

        $this->assertFalse($result->delivered);
        $this->assertTrue($result->permanent);
        $this->assertStringContainsString('could not be encoded', (string) $result->error);
    }

    public function testTheTokenDoesNotRideOutInATransportError(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willThrowException(
            new RuntimeException('dial https://api.telegram.org/bot123:abc/sendMessage: refused')
        );

        $result = new TelegramSender($client)->send($this->channel(), 'hello');

        $this->assertStringNotContainsString('123:abc', (string) $result->error);
        $this->assertStringContainsString('***', (string) $result->error);
    }

    private function channel(): ChannelObject
    {
        $now = Carbon::parse('2026-09-08 12:00:00');

        return new ChannelObject(
            id: 1,
            name: 'ops chat',
            type: NotificationChannelTypeEnum::Telegram,
            enabled: true,
            onOpened: true,
            onEvent: false,
            onClosed: true,
            settings: new TelegramSettingsObject(botToken: '123:abc', chatId: '-100500'),
            createdAt: $now,
            updatedAt: $now
        );
    }
}
