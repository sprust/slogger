<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Exceptions;

use App\Modules\Mcp\Domain\Exceptions\AbstractMcpException;
use App\Modules\Mcp\Domain\Exceptions\McpNotFoundException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use App\Modules\Mcp\Domain\Exceptions\McpServerNameInvalidException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class McpExceptionManager extends BaseExceptionManager
{
    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject
    {
        $exception = $this->detectException($exception);

        if (!$this->isHandlingException($exception)) {
            return null;
        }

        $httpStatus = match (true) {
            $exception instanceof McpNotFoundException          => SymfonyResponse::HTTP_NOT_FOUND,
            $exception instanceof McpServerNameInvalidException => SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR,
            default                                             => SymfonyResponse::HTTP_BAD_REQUEST,
        };

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: $httpStatus,
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof AbstractMcpException;
    }

    protected function getDontReport(): array
    {
        return [McpNotFoundException::class];
    }

    protected function onMakeMessage(Throwable $exception): string
    {
        if ($exception instanceof McpNotFoundException) {
            return $exception->getMessage();
        }

        if ($exception instanceof McpServerNameInvalidException) {
            return 'Server Error';
        }

        return 'Unknown error';
    }
}
