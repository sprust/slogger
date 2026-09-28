<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Protocol;

use Symfony\Component\HttpFoundation\Response;

readonly class McpVersionValidator
{
    public const string SUPPORTED_VERSION = '2026-07-28';

    /**
     * @throws McpProtocolException
     */
    public function validate(McpRequestMessage $message, string $requestedVersion): void
    {
        if ($requestedVersion === self::SUPPORTED_VERSION) {
            return;
        }

        throw new McpProtocolException(
            errorCode: McpErrorCodeEnum::UnsupportedProtocolVersion,
            message: 'Unsupported protocol version',
            httpStatus: Response::HTTP_BAD_REQUEST,
            data: [
                'supported' => [self::SUPPORTED_VERSION],
                'requested' => $requestedVersion,
            ],
            requestId: $message->id
        );
    }
}
