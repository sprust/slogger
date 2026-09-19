<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Services\Senders;

use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Entities\SendResultObject;
use App\Modules\Notification\Entities\Settings\WebhookSettingsObject;
use GuzzleHttp\Psr7\Request;
use JsonException;
use Psr\Http\Client\ClientInterface;
use Throwable;

/**
 * Posts the message as JSON to an address of the user's choosing.
 *
 * The receiver is a program, not a chat, so nothing is marked up: bold and code hand the
 * value back untouched and escape has nothing to escape. What arrives is the text as it
 * was written, the channel it went out on, and when.
 */
readonly class WebhookSender implements NotificationSenderInterface
{
    public const int MAX_TEXT_LENGTH = 10000;

    private const string TOKEN_HEADER = 'X-Slogger-Token';

    public function __construct(
        private ClientInterface $httpClient,
        private HttpSendResultReader $resultReader
    ) {
    }

    public function send(ChannelObject $channel, string $text): SendResultObject
    {
        $settings = $channel->settings;

        if (!$settings instanceof WebhookSettingsObject) {
            return new SendResultObject(
                delivered: false,
                permanent: true,
                error: 'Channel is not a webhook one'
            );
        }

        if ($settings->url === '') {
            return new SendResultObject(
                delivered: false,
                permanent: true,
                error: 'Url is not set'
            );
        }

        try {
            $body = json_encode([
                'channel' => $channel->name,
                'text'    => $this->cut($text),
                'sent_at' => gmdate('c'),
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            return new SendResultObject(
                delivered: false,
                permanent: true,
                error: 'Message could not be encoded: ' . $exception->getMessage()
            );
        }

        $headers = ['Content-Type' => 'application/json'];

        if ($settings->token !== '') {
            $headers[self::TOKEN_HEADER] = $settings->token;
        }

        try {
            $response = $this->httpClient->sendRequest(
                new Request(
                    method: 'POST',
                    uri: $settings->url,
                    headers: $headers,
                    body: $body
                )
            );
        } catch (Throwable $exception) {
            return new SendResultObject(
                delivered: false,
                error: $this->withoutToken($exception->getMessage(), $settings->token)
            );
        }

        return $this->resultReader->read($response, 'Webhook');
    }

    public function escape(string $value): string
    {
        return $value;
    }

    public function bold(string $value): string
    {
        return $value;
    }

    public function code(string $value): string
    {
        return $value;
    }

    private function cut(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_TEXT_LENGTH) {
            return $text;
        }

        return mb_substr($text, 0, self::MAX_TEXT_LENGTH - 1) . '…';
    }

    private function withoutToken(string $message, string $token): string
    {
        if ($token === '') {
            return $message;
        }

        return str_replace($token, '***', $message);
    }
}
