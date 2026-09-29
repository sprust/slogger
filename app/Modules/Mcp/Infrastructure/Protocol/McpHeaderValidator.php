<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Protocol;

use Symfony\Component\HttpFoundation\Response;

readonly class McpHeaderValidator
{
    public const string META_PROTOCOL_VERSION = 'io.modelcontextprotocol/protocolVersion';

    private const array NAMED_METHODS = ['tools/call', 'prompts/get'];

    /**
     * @throws McpProtocolException
     */
    public function validate(McpRequestMessage $message, McpRequestHeaders $headers): string
    {
        $headerVersion = $this->read($message, 'MCP-Protocol-Version', $headers->protocolVersion, encodable: false);

        if (is_null($headerVersion)) {
            throw $this->mismatch(
                $message,
                sprintf(
                    'Header mismatch: MCP-Protocol-Version header is required; this server supports protocol version %s only',
                    McpVersionValidator::SUPPORTED_VERSION
                )
            );
        }

        $meta        = $message->params['_meta'] ?? null;
        $bodyVersion = is_array($meta) ? ($meta[self::META_PROTOCOL_VERSION] ?? null) : null;

        if ($bodyVersion !== $headerVersion) {
            throw $this->mismatch(
                $message,
                sprintf(
                    'Header mismatch: MCP-Protocol-Version header value [%s] does not match params._meta["%s"]',
                    $headerVersion,
                    self::META_PROTOCOL_VERSION
                )
            );
        }

        $headerMethod = $this->read($message, 'Mcp-Method', $headers->method, encodable: false);

        if ($headerMethod !== $message->method) {
            throw $this->mismatch(
                $message,
                sprintf(
                    'Header mismatch: Mcp-Method header value [%s] does not match body value [%s]',
                    $headerMethod ?? '',
                    $message->method
                )
            );
        }

        if (in_array($message->method, self::NAMED_METHODS, true)) {
            $headerName = $this->read($message, 'Mcp-Name', $headers->name, encodable: true);
            $bodyName   = $message->params['name'] ?? null;

            if (is_null($headerName) || $headerName !== $bodyName) {
                throw $this->mismatch(
                    $message,
                    sprintf(
                        'Header mismatch: Mcp-Name header value [%s] does not match params.name',
                        $headerName ?? ''
                    )
                );
            }
        }

        return $headerVersion;
    }

    /**
     * @throws McpProtocolException
     */
    private function read(McpRequestMessage $message, string $header, ?string $value, bool $encodable): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (preg_match('/^[\x20-\x7E\t]+$/', $value) !== 1) {
            throw $this->mismatch($message, "Header mismatch: $header header contains invalid characters");
        }

        if (!$encodable || !str_starts_with($value, '=?base64?') || !str_ends_with($value, '?=')) {
            return $value;
        }

        $decoded = base64_decode(substr($value, 9, -2), true);

        if ($decoded === false) {
            throw $this->mismatch($message, "Header mismatch: $header header is not valid Base64");
        }

        return $decoded;
    }

    private function mismatch(McpRequestMessage $message, string $text): McpProtocolException
    {
        return new McpProtocolException(
            errorCode: McpErrorCodeEnum::HeaderMismatch,
            message: $text,
            httpStatus: Response::HTTP_BAD_REQUEST,
            requestId: $message->id
        );
    }
}
