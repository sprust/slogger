<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Protocol;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

readonly class McpMessageParser
{
    /**
     * @throws McpProtocolException
     */
    public function parse(string $body): McpRequestMessage|McpNotificationMessage
    {
        try {
            $decoded = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new McpProtocolException(
                errorCode: McpErrorCodeEnum::ParseError,
                message: 'Parse error: the body is not valid JSON',
                httpStatus: Response::HTTP_BAD_REQUEST
            );
        }

        if (!is_object($decoded)) {
            throw $this->invalidRequest(
                is_array($decoded)
                    ? 'Invalid request: batches are not supported, send one message per request'
                    : 'Invalid request: the body must be a JSON-RPC 2.0 object'
            );
        }

        $message = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if (($message['jsonrpc'] ?? null) !== '2.0') {
            throw $this->invalidRequest('Invalid request: "jsonrpc" must be "2.0"');
        }

        $method = $message['method'] ?? null;

        if (!is_string($method) || $method === '') {
            throw $this->invalidRequest('Invalid request: "method" must be a non-empty string');
        }

        $params = $decoded->params ?? null;

        if (!is_null($params) && !is_object($params)) {
            throw $this->invalidRequest('Invalid request: "params" must be an object');
        }

        if (!array_key_exists('id', $message)) {
            return new McpNotificationMessage($method);
        }

        $id = $message['id'];

        if (!is_int($id) && !is_string($id)) {
            throw $this->invalidRequest('Invalid request: "id" must be a string or an integer');
        }

        return new McpRequestMessage(
            id: $id,
            method: $method,
            params: is_array($message['params'] ?? null) ? $message['params'] : []
        );
    }

    private function invalidRequest(string $message): McpProtocolException
    {
        return new McpProtocolException(
            errorCode: McpErrorCodeEnum::InvalidRequest,
            message: $message,
            httpStatus: Response::HTTP_BAD_REQUEST
        );
    }
}
