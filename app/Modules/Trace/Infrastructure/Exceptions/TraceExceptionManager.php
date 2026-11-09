<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Exceptions;

use App\Modules\Trace\Domain\Exceptions\TraceNotFoundException;
use App\Modules\Trace\Domain\Exceptions\TraceTreeStateNotFoundException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use Throwable;

class TraceExceptionManager extends BaseExceptionManager
{
    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject
    {
        $exception = $this->detectException($exception);

        if (!$this->isHandlingException($exception)) {
            return null;
        }

        $httpStatus = 404;

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: $httpStatus,
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof TraceNotFoundException
            || $exception instanceof TraceTreeStateNotFoundException;
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
        return $exception->getMessage();
    }
}
