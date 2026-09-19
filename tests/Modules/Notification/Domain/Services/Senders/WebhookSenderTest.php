<?php

declare(strict_types=1);

namespace Tests\Modules\Notification\Domain\Services\Senders;

use App\Modules\Notification\Domain\Services\Senders\HttpSendResultReader;
use App\Modules\Notification\Domain\Services\Senders\WebhookSender;
use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Entities\Settings\WebhookSettingsObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

class WebhookSenderTest extends TestCase
{
    public function testThePayloadNamesTheChannelAndCarriesTheText(): void
    {
        $client = $this->createMock(ClientInterface::class);

        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $body = json_decode((string) $request->getBody(), true);

                self::assertSame('https://ops.example.com/hook', (string) $request->getUri());
                self::assertSame('POST', $request->getMethod());
                self::assertSame('deploys', $body['channel']);
                self::assertSame('hello', $body['text']);
                self::assertNotSame('', $body['sent_at']);
                self::assertSame('secret-token', $request->getHeaderLine('X-Slogger-Token'));

                return true;
            }))
            ->willReturn(new Response(202));

        $result = new WebhookSender($client, new HttpSendResultReader())->send($this->channel(), 'hello');

        self::assertTrue($result->delivered);
    }

    public function testAChannelWithoutATokenSendsNoTokenHeader(): void
    {
        $client = $this->createMock(ClientInterface::class);

        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                self::assertFalse($request->hasHeader('X-Slogger-Token'));

                return true;
            }))
            ->willReturn(new Response(200));

        new WebhookSender($client, new HttpSendResultReader())->send(
            $this->channel(new WebhookSettingsObject(url: 'https://ops.example.com/hook')),
            'hello'
        );
    }

    public function testAChannelWithoutAUrlIsRefusedWithoutARequest(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->never())->method('sendRequest');

        $result = new WebhookSender($client, new HttpSendResultReader())->send(
            $this->channel(new WebhookSettingsObject()),
            'hello'
        );

        self::assertTrue($result->permanent);
        self::assertSame('Url is not set', $result->error);
    }

    public function testTheTokenIsNotWrittenIntoATransportError(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willThrowException(new RuntimeException('sent secret-token, got nothing'));

        $result = new WebhookSender($client, new HttpSendResultReader())->send($this->channel(), 'hello');

        self::assertNotNull($result->error);
        self::assertStringNotContainsString('secret-token', $result->error);
    }

    public function testTheTextIsLeftAsItWasWritten(): void
    {
        $sender = new WebhookSender($this->createMock(ClientInterface::class), new HttpSendResultReader());

        self::assertSame('<b> & co', $sender->escape('<b> & co'));
        self::assertSame('bold', $sender->bold('bold'));
        self::assertSame('code', $sender->code('code'));
    }

    public function testAnOverLongMessageIsCutRatherThanRefused(): void
    {
        $sent = null;

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use (&$sent): Response {
                $sent = json_decode((string) $request->getBody(), true)['text'];

                return new Response(200);
            });

        new WebhookSender($client, new HttpSendResultReader())->send(
            $this->channel(),
            str_repeat('a', WebhookSender::MAX_TEXT_LENGTH + 100)
        );

        self::assertLessThanOrEqual(WebhookSender::MAX_TEXT_LENGTH, mb_strlen((string) $sent));
        self::assertStringEndsWith('…', (string) $sent);
    }

    private function channel(?WebhookSettingsObject $settings = null): ChannelObject
    {
        $now = Carbon::parse('2026-09-08 12:00:00');

        return new ChannelObject(
            id: 1,
            name: 'deploys',
            type: NotificationChannelTypeEnum::Webhook,
            enabled: true,
            settings: $settings ?? new WebhookSettingsObject(
                url: 'https://ops.example.com/hook',
                token: 'secret-token'
            ),
            createdAt: $now,
            updatedAt: $now
        );
    }
}
