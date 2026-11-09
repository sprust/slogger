<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Exceptions;

use App\Modules\Common\Infrastructure\Exceptions\ExceptionManagerInterface;
use App\Modules\Auth\Domain\Exceptions\AuthenticationFailedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

readonly class AuthExceptionManager implements ExceptionManagerInterface
{
    public function map(Throwable $exception): Response|HttpException|null
    {
        return match (true) {
            $exception instanceof AuthenticationFailedException => new HttpException(
                statusCode: 401,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            default => null,
        };
    }
}
