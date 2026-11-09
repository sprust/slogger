<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Exceptions;

use App\Modules\Common\Infrastructure\Exceptions\ExceptionManagerInterface;
use App\Modules\Logs\Domain\Exceptions\LogFileNotDeletableException;
use App\Modules\Logs\Domain\Exceptions\LogFileTooLargeException;
use App\Modules\Logs\Domain\Exceptions\LogCursorInvalidException;
use App\Modules\Logs\Domain\Exceptions\RequestedLogFileNotFoundException;
use App\Modules\Logs\Domain\Exceptions\LogFileIndexingException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

readonly class LogsExceptionManager implements ExceptionManagerInterface
{
    public function map(Throwable $exception): Response|HttpException|null
    {
        return match (true) {
            $exception instanceof LogFileNotDeletableException => new HttpException(
                statusCode: 422,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            $exception instanceof LogFileTooLargeException => new HttpException(
                statusCode: 422,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            $exception instanceof LogCursorInvalidException => new HttpException(
                statusCode: 422,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            $exception instanceof RequestedLogFileNotFoundException => new HttpException(
                statusCode: 404,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            $exception instanceof LogFileIndexingException => new HttpException(
                statusCode: 409,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            default => null,
        };
    }
}
