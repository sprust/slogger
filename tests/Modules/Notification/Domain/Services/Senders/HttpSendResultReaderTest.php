<?php

declare(strict_types=1);

namespace Tests\Modules\Notification\Domain\Services\Senders;

use App\Modules\Notification\Domain\Services\Senders\HttpSendResultReader;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HttpSendResultReaderTest extends TestCase
{
    #[DataProvider('deliveredProvider')]
    public function testAnyTwoHundredIsADelivery(int $status): void
    {
        $result = new HttpSendResultReader()->read(new Response($status), 'Slack');

        self::assertTrue($result->delivered);
        self::assertNull($result->error);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function deliveredProvider(): array
    {
        return [
            '200' => [200],
            '201' => [201],
            '204' => [204],
        ];
    }

    public function testTooManyRequestsWaitsAsLongAsAsked(): void
    {
        $result = new HttpSendResultReader()->read(
            new Response(429, ['Retry-After' => '37'], 'rate_limited'),
            'Slack'
        );

        self::assertFalse($result->delivered);
        self::assertFalse($result->permanent);
        self::assertSame(37, $result->retryAfterSeconds);
    }

    public function testTooManyRequestsWithoutANumberFallsBackToADefault(): void
    {
        $result = new HttpSendResultReader()->read(new Response(429), 'Slack');

        self::assertSame(60, $result->retryAfterSeconds);
    }

    public function testAClientErrorIsRefusedForGood(): void
    {
        $result = new HttpSendResultReader()->read(new Response(404, body: 'channel_not_found'), 'Slack');

        self::assertTrue($result->permanent);
        self::assertSame('Slack answered 404: channel_not_found', $result->error);
    }

    public function testAServerErrorIsWorthAnotherGo(): void
    {
        $result = new HttpSendResultReader()->read(new Response(503), 'Webhook');

        self::assertFalse($result->delivered);
        self::assertFalse($result->permanent);
        self::assertSame('Webhook answered 503', $result->error);
    }

    public function testAnErrorCarriesOnlyTheHeadOfALongBody(): void
    {
        $result = new HttpSendResultReader()->read(
            new Response(400, body: str_repeat('a', 5000)),
            'Webhook'
        );

        self::assertNotNull($result->error);
        self::assertLessThan(300, mb_strlen($result->error));
    }
}
