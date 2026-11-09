<?php

declare(strict_types=1);

namespace App\Modules\Service\Infrastructure\Exceptions;

use App\Modules\Service\Domain\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Service\Domain\Exceptions\ServiceNotFoundException;
use App\Modules\Service\Domain\Exceptions\ServicesSortMismatchException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use Throwable;

class ServiceExceptionManager extends BaseExceptionManager
{
    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject
    {
        $exception = $this->detectException($exception);

        if (!$this->isHandlingException($exception)) {
            return null;
        }

        $httpStatus = match (true) {
            $exception instanceof ServiceAlreadyExistsException => 422,
            $exception instanceof ServiceNotFoundException      => 404,
            $exception instanceof ServicesSortMismatchException => 422,
            default                                             => 400,
        };

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: $httpStatus,
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof ServiceAlreadyExistsException
            || $exception instanceof ServiceNotFoundException
            || $exception instanceof ServicesSortMismatchException;
    }

    protected function getDontReport(): array
    {
        return [
            ServiceAlreadyExistsException::class,
            ServiceNotFoundException::class,
            ServicesSortMismatchException::class,
        ];
    }

    protected function onMakeMessage(Throwable $exception): string
    {
        return $exception->getMessage();
    }
}
