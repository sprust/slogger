<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Exceptions;

use App\Modules\Common\Infrastructure\Exceptions\ExceptionManagerInterface;
use App\Modules\User\Domain\Exceptions\UserAlreadyExistsException;
use App\Modules\User\Domain\Exceptions\UserNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

readonly class UserExceptionManager implements ExceptionManagerInterface
{
    public function map(Throwable $exception): Response|HttpException|null
    {
        return match (true) {
            $exception instanceof UserAlreadyExistsException => new HttpException(
                statusCode: 422,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            $exception instanceof UserNotFoundException => new HttpException(
                statusCode: 404,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            default => null,
        };
    }
}
