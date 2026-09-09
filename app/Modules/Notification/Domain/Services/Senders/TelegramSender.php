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

    /**
     * How much of the limit a cut message keeps free: one ellipsis and the closing tags
     * the repair may add. `<b>` and `<code>` cannot nest more than a couple deep in
     * anything this sends, and this leaves room for several.
     */
    private const int CUT_RESERVE = 64;

    public function __construct(
        private ClientInterface $httpClient
    ) {
    }

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

        try {
            $body = json_encode([
                'chat_id'                  => $settings->chatId,
                'text'                     => $this->cut($text),
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => true,
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            return new SendResultObject(
                delivered: false,
                permanent: true,
                error: 'Message could not be encoded: ' . $exception->getMessage()
            );
        }

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
            return new SendResultObject(
                delivered: false,
                error: $this->withoutToken($exception->getMessage(), $settings->botToken)
            );
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

    private function withoutToken(string $message, string $botToken): string
    {
        return str_replace($botToken, '***', $message);
    }

    /**
     * The message, shortened to what Telegram takes, still parseable as HTML.
     *
     * A plain cut is not enough. The text arrives already marked up and already escaped,
     * so the character at the limit can be the middle of `&amp;` or of `<code>`, and
     * Telegram answers "can't parse entities" with a 400 — which this reads as permanent,
     * so the notification is lost rather than shortened. The tail is therefore trimmed
     * back off a half-written entity or tag, and whatever was left open is closed.
     */
    private function cut(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_TEXT_LENGTH) {
            return $text;
        }

        // Room left for the ellipsis and for the tags the close below may add back, so
        // that repairing the cut cannot push the message over the limit again.
        $cut = mb_substr($text, 0, self::MAX_TEXT_LENGTH - self::CUT_RESERVE);

        // A `<` or an `&` with no end in what survived started something the cut took the
        // rest of.
        $cut = (string) preg_replace('/<[^<>]*$/u', '', $cut);
        $cut = (string) preg_replace('/&[^&;<]*$/u', '', $cut);

        return $this->closeTags($cut) . '…';
    }

    /**
     * Closes the tags this sender opens and the cut left open, innermost first.
     *
     * Only the two it writes itself — the text is escaped everywhere else, so nothing
     * else can be a tag at all.
     */
    private function closeTags(string $text): string
    {
        $open = [];

        preg_match_all('#</?(b|code)>#u', $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (str_starts_with($match[0], '</')) {
                array_pop($open);

                continue;
            }

            $open[] = $match[1];
        }

        foreach (array_reverse($open) as $tag) {
            $text .= "</$tag>";
        }

        return $text;
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
