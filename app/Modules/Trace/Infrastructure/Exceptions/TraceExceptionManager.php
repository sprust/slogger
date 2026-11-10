<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Exceptions;

use App\Modules\Trace\Domain\Exceptions\AbstractTraceException;
use App\Modules\Trace\Domain\Exceptions\TraceNotFoundException;
use App\Modules\Trace\Domain\Exceptions\TraceTreeStateNotFoundException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class TraceExceptionManager extends BaseExceptionManager
{
    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject
    {
        $exception = $this->detectException($exception);

        if (!$this->isHandlingException($exception)) {
            return null;
        }

        $httpStatus = match (true) {
            $exception instanceof TraceNotFoundException          => SymfonyResponse::HTTP_NOT_FOUND,
            $exception instanceof TraceTreeStateNotFoundException => SymfonyResponse::HTTP_NOT_FOUND,
            default                                               => SymfonyResponse::HTTP_BAD_REQUEST,
        };

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: $httpStatus,
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof AbstractTraceException;
    }

    protected function getDontReport(): array
    {
        return [
            TraceNotFoundException::class,
            TraceTreeStateNotFoundException::class,
        ];
    }

    protected function onMakeMessage(Throwable $exception): string
    {
        if ($exception instanceof TraceNotFoundException) {
            return $exception->getMessage();
        }

        if ($exception instanceof TraceTreeStateNotFoundException) {
            return $exception->getMessage();
        }

        return 'Unknown error';
    }
}
