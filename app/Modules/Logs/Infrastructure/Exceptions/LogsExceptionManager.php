<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Exceptions;

use App\Modules\Logs\Domain\Exceptions\AbstractLogsException;
use App\Modules\Logs\Domain\Exceptions\LogFileNotDeletableException;
use App\Modules\Logs\Domain\Exceptions\LogFileTooLargeException;
use App\Modules\Logs\Domain\Exceptions\LogCursorInvalidException;
use App\Modules\Logs\Domain\Exceptions\RequestedLogFileNotFoundException;
use App\Modules\Logs\Domain\Exceptions\LogFileIndexingException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use App\Modules\Logs\Domain\Exceptions\LogFileNotFoundException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
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
            $exception instanceof LogFileNotDeletableException,
            $exception instanceof LogFileTooLargeException,
            $exception instanceof LogCursorInvalidException         => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
            $exception instanceof RequestedLogFileNotFoundException => SymfonyResponse::HTTP_NOT_FOUND,
            $exception instanceof LogFileIndexingException          => SymfonyResponse::HTTP_CONFLICT,
            $exception instanceof LogFileNotFoundException          => SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR,
            default                                                 => SymfonyResponse::HTTP_BAD_REQUEST,
        };

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: $httpStatus,
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof AbstractLogsException;
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
        if ($exception instanceof LogFileNotDeletableException) {
            return $exception->getMessage();
        }

        if ($exception instanceof LogFileTooLargeException) {
            return $exception->getMessage();
        }

        if ($exception instanceof LogCursorInvalidException) {
            return $exception->getMessage();
        }

        if ($exception instanceof RequestedLogFileNotFoundException) {
            return $exception->getMessage();
        }

        if ($exception instanceof LogFileIndexingException) {
            return $exception->getMessage();
        }

        if ($exception instanceof LogFileNotFoundException) {
            return 'Server Error';
        }

        return 'Unknown error';
    }
}
