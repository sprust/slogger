<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Exceptions;

use App\Modules\Logs\Domain\Exceptions\LogFileNotDeletableException;
use App\Modules\Logs\Domain\Exceptions\LogFileTooLargeException;
use App\Modules\Logs\Domain\Exceptions\LogCursorInvalidException;
use App\Modules\Logs\Domain\Exceptions\RequestedLogFileNotFoundException;
use App\Modules\Logs\Domain\Exceptions\LogFileIndexingException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use Throwable;

class LogsExceptionManager extends BaseExceptionManager
{
    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject
    {
        $exception = $this->detectException($exception);

        if (!$this->isHandlingException($exception)) {
            return null;
        }

        $httpStatus = match (true) {
            $exception instanceof LogFileNotDeletableException      => 422,
            $exception instanceof LogFileTooLargeException          => 422,
            $exception instanceof LogCursorInvalidException         => 422,
            $exception instanceof RequestedLogFileNotFoundException => 404,
            $exception instanceof LogFileIndexingException          => 409,
            default                                                 => 400,
        };

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: $httpStatus,
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof LogFileNotDeletableException
            || $exception instanceof LogFileTooLargeException
            || $exception instanceof LogCursorInvalidException
            || $exception instanceof RequestedLogFileNotFoundException
            || $exception instanceof LogFileIndexingException;
    }

    protected function getDontReport(): array
    {
        return [
            LogFileNotDeletableException::class,
            LogFileTooLargeException::class,
            LogCursorInvalidException::class,
            RequestedLogFileNotFoundException::class,
            LogFileIndexingException::class,
        ];
    }

    protected function onMakeMessage(Throwable $exception): string
    {
        return $exception->getMessage();
    }
}
