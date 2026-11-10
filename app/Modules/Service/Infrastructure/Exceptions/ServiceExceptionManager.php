<?php

declare(strict_types=1);

namespace App\Modules\Service\Infrastructure\Exceptions;

use App\Modules\Service\Domain\Exceptions\AbstractServiceException;
use App\Modules\Service\Domain\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Service\Domain\Exceptions\ServiceNotFoundException;
use App\Modules\Service\Domain\Exceptions\ServicesSortMismatchException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
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
            $exception instanceof ServiceAlreadyExistsException => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
            $exception instanceof ServiceNotFoundException      => SymfonyResponse::HTTP_NOT_FOUND,
            $exception instanceof ServicesSortMismatchException => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
            default                                             => SymfonyResponse::HTTP_BAD_REQUEST,
        };

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: $httpStatus,
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof AbstractServiceException;
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
        if ($exception instanceof ServiceAlreadyExistsException) {
            return $exception->getMessage();
        }

        if ($exception instanceof ServiceNotFoundException) {
            return $exception->getMessage();
        }

        if ($exception instanceof ServicesSortMismatchException) {
            return $exception->getMessage();
        }

        return 'Unknown error';
    }
}
