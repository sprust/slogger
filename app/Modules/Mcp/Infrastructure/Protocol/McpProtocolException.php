<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Protocol;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class McpProtocolException extends Exception
{
    /**
     * @param array<string, mixed>|null $data
     */
    public function __construct(
        public readonly McpErrorCodeEnum $errorCode,
        string $message,
        public readonly int $httpStatus = Response::HTTP_OK,
        public readonly ?array $data = null,
        public readonly int|string|null $requestId = null
    ) {
        parent::__construct($message);
    }
}
