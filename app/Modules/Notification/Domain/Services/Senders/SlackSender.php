<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Services\Senders;

use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Entities\SendResultObject;
use App\Modules\Notification\Entities\Settings\SlackSettingsObject;
use GuzzleHttp\Psr7\Request;
use JsonException;
use Psr\Http\Client\ClientInterface;
use Throwable;

readonly class SlackSender implements NotificationSenderInterface
{
    public const int MAX_TEXT_LENGTH = 40000;

    public function __construct(
        private ClientInterface $httpClient,
        private HttpSendResultReader $resultReader
    ) {
    }

    public function send(ChannelObject $channel, string $text): SendResultObject
    {
        $settings = $channel->settings;

        if (!$settings instanceof SlackSettingsObject) {
            return new SendResultObject(
                delivered: false,
                permanent: true,
                error: 'Channel is not a slack one'
            );
        }

        if ($settings->webhookUrl === '') {
            return new SendResultObject(
                delivered: false,
                permanent: true,
                error: 'Webhook url is not set'
            );
        }

        try {
            $body = json_encode([
                'text' => $this->cut($text),
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
                    uri: $settings->webhookUrl,
                    headers: ['Content-Type' => 'application/json'],
                    body: $body
                )
            );
        } catch (Throwable $exception) {
            return new SendResultObject(
                delivered: false,
                error: $this->withoutUrl($exception->getMessage(), $settings->webhookUrl)
            );
        }

        return $this->resultReader->read($response, 'Slack');
    }

    /**
     * Slack reads `&`, `<` and `>` as the start of its own markup, and these three are
     * the whole of what it asks a sender to escape.
     */
    public function escape(string $value): string
    {
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $value);
    }

    public function bold(string $value): string
    {
        return "*$value*";
    }

    public function code(string $value): string
    {
        return "`$value`";
    }

    /**
     * The message, shortened to what Slack takes.
     *
     * mrkdwn has no tags to leave open, so the cut only has to keep a half-written `&amp;`
     * out of the tail — Slack would print the leftover as text.
     */
    private function cut(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_TEXT_LENGTH) {
            return $text;
        }

        $cut = mb_substr($text, 0, self::MAX_TEXT_LENGTH - 1);

        $cut = (string) preg_replace('/&[^&;<]*$/u', '', $cut);

        return $cut . '…';
    }

    /**
     * The url is the credential here: anyone holding it can post to the channel, so it
     * does not go into an error a delivery log keeps.
     */
    private function withoutUrl(string $message, string $webhookUrl): string
    {
        return str_replace($webhookUrl, '***', $message);
    }
}
