<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Services\Senders;

use App\Modules\Notification\Entities\SendResultObject;
use Psr\Http\Message\ResponseInterface;

/**
 * What an ordinary HTTP answer says about a delivery.
 *
 * The rules are the plain HTTP ones and are the same for every endpoint that does not
 * describe its own outcome in the body: 2xx delivered, 429 a wait the server names, the
 * rest of 4xx the caller's fault and not worth repeating, 5xx worth another go. Telegram
 * has none of this because it answers 200 with `ok: false`.
 */
readonly class HttpSendResultReader
{
    private const int DEFAULT_RETRY_AFTER_SECONDS = 60;

    private const int MAX_REPORTED_BODY_LENGTH = 200;

    public function read(ResponseInterface $response, string $endpointName): SendResultObject
    {
        $status = $response->getStatusCode();

        if ($status >= 200 && $status < 300) {
            return new SendResultObject(delivered: true);
        }

        $error = $this->describe($response, $endpointName, $status);

        if ($status === 429) {
            return new SendResultObject(
                delivered: false,
                error: $error,
                retryAfterSeconds: $this->retryAfter($response)
            );
        }

        if ($status >= 400 && $status < 500) {
            return new SendResultObject(delivered: false, permanent: true, error: $error);
        }

        return new SendResultObject(delivered: false, error: $error);
    }

    private function describe(ResponseInterface $response, string $endpointName, int $status): string
    {
        $body = trim((string) $response->getBody());

        if ($body === '') {
            return "$endpointName answered $status";
        }

        return "$endpointName answered $status: " . mb_substr($body, 0, self::MAX_REPORTED_BODY_LENGTH);
    }

    private function retryAfter(ResponseInterface $response): int
    {
        $retryAfter = $response->getHeaderLine('Retry-After');

        return is_numeric($retryAfter) ? (int) $retryAfter : self::DEFAULT_RETRY_AFTER_SECONDS;
    }
}
