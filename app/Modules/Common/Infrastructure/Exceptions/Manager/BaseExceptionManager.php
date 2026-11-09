<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Exceptions\Manager;

use Illuminate\Routing\Exceptions\StreamedResponseException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

abstract class BaseExceptionManager implements ExceptionManagerInterface
{
    abstract protected function isHandlingException(Throwable $exception): bool;

    /** @return class-string<Throwable>[] */
    abstract protected function getDontReport(): array;

    abstract protected function onMakeMessage(Throwable $exception): string;

    public function isReportable(Throwable $exception): ?bool
    {
        $exception = $this->detectException($exception);

        if (!$this->isHandlingException($exception)) {
            return null;
        }

        return !in_array($exception::class, $this->getDontReport());
    }

    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject
    {
        $exception = $this->detectException($exception);

        if (!$this->isHandlingException($exception)) {
            return null;
        }

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: Response::HTTP_BAD_REQUEST
        );
    }

    protected function detectException(Throwable $exception): Throwable
    {
        return $exception instanceof StreamedResponseException
            ? $exception->originalException
            : $exception;
    }
}
