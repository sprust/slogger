<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Http\Controllers;

use App\Modules\Mcp\Infrastructure\Protocol\McpErrorCodeEnum;
use App\Modules\Mcp\Infrastructure\Protocol\McpHeaderValidator;
use App\Modules\Mcp\Infrastructure\Protocol\McpJsonEncoder;
use App\Modules\Mcp\Infrastructure\Protocol\McpMessageParser;
use App\Modules\Mcp\Infrastructure\Protocol\McpNotificationMessage;
use App\Modules\Mcp\Infrastructure\Protocol\McpProtocolException;
use App\Modules\Mcp\Infrastructure\Protocol\McpRequestHeaders;
use App\Modules\Mcp\Infrastructure\Protocol\McpServer;
use App\Modules\Mcp\Infrastructure\Protocol\McpVersionValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class McpEndpointController
{
    public function __construct(
        private McpMessageParser $messageParser,
        private McpHeaderValidator $headerValidator,
        private McpVersionValidator $versionValidator,
        private McpServer $server
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            $message = $this->messageParser->parse($request->getContent());

            if ($message instanceof McpNotificationMessage) {
                return response()->noContent(Response::HTTP_ACCEPTED);
            }

            $version = $this->headerValidator->validate(
                message: $message,
                headers: new McpRequestHeaders(
                    protocolVersion: $request->headers->get('MCP-Protocol-Version'),
                    method: $request->headers->get('Mcp-Method'),
                    name: $request->headers->get('Mcp-Name')
                )
            );

            $this->versionValidator->validate($message, $version);

            return $this->json(
                status: Response::HTTP_OK,
                body: [
                    'jsonrpc' => '2.0',
                    'id'      => $message->id,
                    'result'  => $this->server->handle($message),
                ]
            );
        } catch (McpProtocolException $exception) {
            return $this->error(
                status: $exception->httpStatus,
                id: $exception->requestId,
                code: $exception->errorCode,
                message: $exception->getMessage(),
                data: $exception->data
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->error(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                id: isset($message) && !$message instanceof McpNotificationMessage ? $message->id : null,
                code: McpErrorCodeEnum::InternalError,
                message: 'Internal error'
            );
        }
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private function error(
        int $status,
        int|string|null $id,
        McpErrorCodeEnum $code,
        string $message,
        ?array $data = null
    ): JsonResponse {
        $error = [
            'code'    => $code->value,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $error['data'] = $data;
        }

        return $this->json(
            status: $status,
            body: [
                'jsonrpc' => '2.0',
                'id'      => $id,
                'error'   => $error,
            ]
        );
    }

    /**
     * @param array<string, mixed> $body
     */
    private function json(int $status, array $body): JsonResponse
    {
        return new JsonResponse(
            data: $body,
            status: $status,
            options: McpJsonEncoder::FLAGS
        );
    }
}
