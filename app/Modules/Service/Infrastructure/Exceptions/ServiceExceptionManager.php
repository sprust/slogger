<?php

declare(strict_types=1);

namespace App\Modules\Service\Infrastructure\Exceptions;

use App\Modules\Common\Infrastructure\Exceptions\ExceptionManagerInterface;
use App\Modules\Service\Domain\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Service\Domain\Exceptions\ServiceNotFoundException;
use App\Modules\Service\Domain\Exceptions\ServicesSortMismatchException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

readonly class ServiceExceptionManager implements ExceptionManagerInterface
{
    public function map(Throwable $exception): Response|HttpException|null
    {
        return match (true) {
            $exception instanceof ServiceAlreadyExistsException => new HttpException(
                statusCode: 422,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            $exception instanceof ServiceNotFoundException => new HttpException(
                statusCode: 404,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            $exception instanceof ServicesSortMismatchException => new HttpException(
                statusCode: 422,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            default => null,
        };
    }
}
