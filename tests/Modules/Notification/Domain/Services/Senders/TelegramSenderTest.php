<?php

namespace Tests\Modules\Notification\Domain\Services\Senders;

use App\Modules\Notification\Domain\Services\Senders\TelegramSender;
use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Entities\Settings\TelegramSettingsObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

class TelegramSenderTest extends TestCase
{
    public function testAnOkAnswerIsADelivery(): void
    {
        $result = $this->sender(new Response(200, body: '{"ok":true,"result":{}}'))
            ->send($this->channel(), 'hello');

        $this->assertTrue($result->delivered);
        $this->assertNull($result->error);
    }

    public function testAnOkFalseAnswerIsNotADelivery(): void
    {
        $result = $this->sender(new Response(200, body: '{"ok":false,"description":"nope"}'))
            ->send($this->channel(), 'hello');

        $this->assertFalse($result->delivered);
        $this->assertSame('nope', $result->error);
    }

    public function testTooManyRequestsWaitsExactlyAsLongAsAsked(): void
    {
        $result = $this->sender(
            new Response(429, body: '{"ok":false,"description":"Too Many Requests","parameters":{"retry_after":37}}')
        )->send($this->channel(), 'hello');

        $this->assertFalse($result->delivered);
        $this->assertFalse($result->permanent);
        $this->assertSame(37, $result->retryAfterSeconds);
    }

    public function testTooManyRequestsWithoutANumberFallsBackToADefault(): void
    {
        $result = $this->sender(new Response(429, body: '{"ok":false}'))->send($this->channel(), 'hello');

        $this->assertSame(60, $result->retryAfterSeconds);
    }

    public function testAClientErrorIsRefusedForGood(): void
    {
        $result = $this->sender(new Response(401, body: '{"ok":false,"description":"Unauthorized"}'))
            ->send($this->channel(), 'hello');

        $this->assertFalse($result->delivered);
        $this->assertTrue($result->permanent);
        $this->assertSame('Unauthorized', $result->error);
    }

    public function testAServerErrorIsWorthAnotherGo(): void
    {
        $result = $this->sender(new Response(502, body: 'Bad Gateway'))->send($this->channel(), 'hello');

        $this->assertFalse($result->delivered);
        $this->assertFalse($result->permanent);
        $this->assertNull($result->retryAfterSeconds);
    }

    public function testATransportFailureIsWorthAnotherGo(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willThrowException(new RuntimeException('connection reset'));

        $result = new TelegramSender($client)->send($this->channel(), 'hello');

        $this->assertFalse($result->delivered);
        $this->assertFalse($result->permanent);
        $this->assertSame('connection reset', $result->error);
    }

    public function testAChannelWithoutATokenIsRefusedWithoutARequest(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->never())->method('sendRequest');

        $result = new TelegramSender($client)->send(
            $this->channel(new TelegramSettingsObject(botToken: '', chatId: '42')),
            'hello'
        );

        $this->assertTrue($result->permanent);
    }

    public function testTheTokenAndTheChatIdReachTelegram(): void
    {
        $client = $this->createMock(ClientInterface::class);

        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $body = json_decode((string) $request->getBody(), true);

                $this->assertSame('https://api.telegram.org/bot123:abc/sendMessage', (string) $request->getUri());
                $this->assertSame('-100500', $body['chat_id']);
                $this->assertSame('hello', $body['text']);
                $this->assertSame('HTML', $body['parse_mode']);

                return true;
            }))
            ->willReturn(new Response(200, body: '{"ok":true}'));

        new TelegramSender($client)->send($this->channel(), 'hello');
    }

    public function testMarkupInAValueIsEscaped(): void
    {
        $sender = $this->sender(new Response(200, body: '{"ok":true}'));

        $this->assertSame('&lt;b&gt; &amp; co', $sender->escape('<b> & co'));
    }

    public function testAnOverLongMessageIsCutRatherThanRefused(): void
    {
        $sent = null;

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use (&$sent): Response {
                $sent = json_decode((string) $request->getBody(), true)['text'];

                return new Response(200, body: '{"ok":true}');
            });

        new TelegramSender($client)->send($this->channel(), str_repeat('a', 5000));

        $this->assertLessThanOrEqual(TelegramSender::MAX_TEXT_LENGTH, mb_strlen($sent));
        $this->assertStringEndsWith('…', $sent);
    }

    /**
     * The text is already marked up and already escaped by the time it gets here, so the
     * character at the limit can be the middle of a tag or of an entity. Telegram answers
     * a broken one with a 400, which this reads as permanent — the message is then lost
     * rather than shortened.
     */
    public function testACutInTheMiddleOfMarkupLeavesTheMessageParseable(): void
    {
        $sent = null;

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use (&$sent): Response {
                $sent = json_decode((string) $request->getBody(), true)['text'];

                return new Response(200, body: '{"ok":true}');
            });

        // Long enough that the cut lands inside the trailing run of markup and entities.
        $text = '<b>' . str_repeat('a', TelegramSender::MAX_TEXT_LENGTH)
            . str_repeat('<code>x</code>&amp;', 20) . '</b>';

        new TelegramSender($client)->send($this->channel(), $text);

        $this->assertLessThanOrEqual(TelegramSender::MAX_TEXT_LENGTH, mb_strlen($sent));
        $this->assertSame(
            substr_count((string) $sent, '<b>'),
            substr_count((string) $sent, '</b>'),
            'the bold the cut left open was not closed'
        );
        $this->assertSame(
            substr_count((string) $sent, '<code>'),
            substr_count((string) $sent, '</code>'),
            'a code tag the cut left open was not closed'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<[^<>]*$|&[^&;<]*$/u',
            (string) preg_replace('/…$/u', '', (string) $sent),
            'the cut left half a tag or half an entity at the end'
        );
    }

    public function testAMessageAtTheLimitIsSentWhole(): void
    {
        $sent = null;

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use (&$sent): Response {
                $sent = json_decode((string) $request->getBody(), true)['text'];

                return new Response(200, body: '{"ok":true}');
            });

        $text = str_repeat('a', TelegramSender::MAX_TEXT_LENGTH);

        new TelegramSender($client)->send($this->channel(), $text);

        $this->assertSame($text, $sent);
    }

    private function sender(Response $response): TelegramSender
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        return new TelegramSender($client);
    }

    private function channel(?TelegramSettingsObject $settings = null): ChannelObject
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
            settings: $settings ?? new TelegramSettingsObject(botToken: '123:abc', chatId: '-100500'),
            createdAt: $now,
            updatedAt: $now
        );
    }
}
