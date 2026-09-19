<?php

declare(strict_types=1);

namespace Tests\Modules\Notification\Domain\Services\Senders;

use App\Modules\Notification\Domain\Services\Senders\HttpSendResultReader;
use App\Modules\Notification\Domain\Services\Senders\SlackSender;
use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Entities\Settings\SlackSettingsObject;
use App\Modules\Notification\Entities\Settings\TelegramSettingsObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

class SlackSenderTest extends TestCase
{
    private const string WEBHOOK_URL = 'https://hooks.slack.com/services/T1/B2/abcdef';

    public function testTheTextReachesTheWebhookUrl(): void
    {
        $client = $this->createMock(ClientInterface::class);

        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $body = json_decode((string) $request->getBody(), true);

                self::assertSame(self::WEBHOOK_URL, (string) $request->getUri());
                self::assertSame('POST', $request->getMethod());
                self::assertSame('hello', $body['text']);

                return true;
            }))
            ->willReturn(new Response(200, body: 'ok'));

        $result = new SlackSender($client, new HttpSendResultReader())->send($this->channel(), 'hello');

        self::assertTrue($result->delivered);
    }

    public function testAChannelOfAnotherTypeIsRefusedWithoutARequest(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->never())->method('sendRequest');

        $result = new SlackSender($client, new HttpSendResultReader())->send(
            $this->channel(new TelegramSettingsObject()),
            'hello'
        );

        self::assertTrue($result->permanent);
    }

    public function testAChannelWithoutAUrlIsRefusedWithoutARequest(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->never())->method('sendRequest');

        $result = new SlackSender($client, new HttpSendResultReader())->send(
            $this->channel(new SlackSettingsObject()),
            'hello'
        );

        self::assertTrue($result->permanent);
        self::assertSame('Webhook url is not set', $result->error);
    }

    public function testAKnownBadChannelIsRefusedForGood(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn(new Response(404, body: 'channel_not_found'));

        $result = new SlackSender($client, new HttpSendResultReader())->send($this->channel(), 'hello');

        self::assertTrue($result->permanent);
        self::assertSame('Slack answered 404: channel_not_found', $result->error);
    }

    public function testTheUrlIsNotWrittenIntoATransportError(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')
            ->willThrowException(new RuntimeException('connect to ' . self::WEBHOOK_URL . ' failed'));

        $result = new SlackSender($client, new HttpSendResultReader())->send($this->channel(), 'hello');

        self::assertFalse($result->delivered);
        self::assertNotNull($result->error);
        self::assertStringNotContainsString('abcdef', $result->error);
    }

    public function testTheMarkupIsSlackOwn(): void
    {
        $sender = new SlackSender($this->createMock(ClientInterface::class), new HttpSendResultReader());

        self::assertSame('*bold*', $sender->bold('bold'));
        self::assertSame('`code`', $sender->code('code'));
        self::assertSame('&lt;b&gt; &amp; co', $sender->escape('<b> & co'));
    }

    public function testAnOverLongMessageIsCutRatherThanRefused(): void
    {
        $sent = null;

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use (&$sent): Response {
                $sent = json_decode((string) $request->getBody(), true)['text'];

                return new Response(200, body: 'ok');
            });

        new SlackSender($client, new HttpSendResultReader())->send(
            $this->channel(),
            str_repeat('a', SlackSender::MAX_TEXT_LENGTH + 100)
        );

        self::assertLessThanOrEqual(SlackSender::MAX_TEXT_LENGTH, mb_strlen((string) $sent));
        self::assertStringEndsWith('…', (string) $sent);
    }

    private function channel(
        TelegramSettingsObject|SlackSettingsObject|null $settings = null
    ): ChannelObject {
        $now = Carbon::parse('2026-09-08 12:00:00');

        return new ChannelObject(
            id: 1,
            name: 'ops channel',
            type: NotificationChannelTypeEnum::Slack,
            enabled: true,
            settings: $settings ?? new SlackSettingsObject(webhookUrl: self::WEBHOOK_URL),
            createdAt: $now,
            updatedAt: $now
        );
    }
}
