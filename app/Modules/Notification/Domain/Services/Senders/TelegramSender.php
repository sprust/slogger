<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Services\Senders;

use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Entities\SendResultObject;
use App\Modules\Notification\Entities\Settings\TelegramSettingsObject;
use GuzzleHttp\Psr7\Request;
use JsonException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

readonly class TelegramSender implements NotificationSenderInterface
{
    public const int MAX_TEXT_LENGTH = 4096;

    private const string ENDPOINT = 'https://api.telegram.org/bot%s/sendMessage';

    private const int DEFAULT_RETRY_AFTER_SECONDS = 60;

    public function __construct(
        private ClientInterface $httpClient
    ) {
    }

    /**
     * @throws JsonException
     */
    public function send(ChannelObject $channel, string $text): SendResultObject
    {
        $settings = $channel->settings;

        if (!$settings instanceof TelegramSettingsObject) {
            return new SendResultObject(
                delivered: false,
                permanent: true,
                error: 'Channel is not a telegram one'
            );
        }

        if ($settings->botToken === '' || $settings->chatId === '') {
            return new SendResultObject(
                delivered: false,
                permanent: true,
                error: 'Bot token or chat id is not set'
            );
        }

        $body = json_encode([
            'chat_id'                  => $settings->chatId,
            'text'                     => $this->cut($text),
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        try {
            $response = $this->httpClient->sendRequest(
                new Request(
                    method: 'POST',
                    uri: sprintf(self::ENDPOINT, $settings->botToken),
                    headers: ['Content-Type' => 'application/json'],
                    body: $body
                )
            );
        } catch (Throwable $exception) {
            return new SendResultObject(delivered: false, error: $exception->getMessage());
        }

        return $this->read($response);
    }

    public function escape(string $value): string
    {
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $value);
    }

    public function bold(string $value): string
    {
        return "<b>$value</b>";
    }

    public function code(string $value): string
    {
        return "<code>$value</code>";
    }

    private function cut(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_TEXT_LENGTH) {
            return $text;
        }

        return mb_substr($text, 0, self::MAX_TEXT_LENGTH - 1) . '…';
    }

    private function read(ResponseInterface $response): SendResultObject
    {
        $status = $response->getStatusCode();

        $payload = json_decode((string) $response->getBody(), true);
        $payload = is_array($payload) ? $payload : [];

        if ($status === 200 && ($payload['ok'] ?? false) === true) {
            return new SendResultObject(delivered: true);
        }

        $error = is_string($payload['description'] ?? null)
            ? $payload['description']
            : "Telegram answered $status";

        if ($status === 429) {
            $retryAfter = $payload['parameters']['retry_after'] ?? null;

            return new SendResultObject(
                delivered: false,
                error: $error,
                retryAfterSeconds: is_numeric($retryAfter)
                    ? (int) $retryAfter
                    : self::DEFAULT_RETRY_AFTER_SECONDS
            );
        }

        if ($status >= 400 && $status < 500) {
            return new SendResultObject(delivered: false, permanent: true, error: $error);
        }

        return new SendResultObject(delivered: false, error: $error);
    }
}
