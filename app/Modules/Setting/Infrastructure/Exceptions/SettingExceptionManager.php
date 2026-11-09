<?php

declare(strict_types=1);

namespace App\Modules\Setting\Infrastructure\Exceptions;

use App\Modules\Common\Infrastructure\Exceptions\ExceptionManagerInterface;
use App\Modules\Setting\Domain\Exceptions\SettingValueOutOfRangeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

readonly class SettingExceptionManager implements ExceptionManagerInterface
{
    public function map(Throwable $exception): Response|HttpException|null
    {
        return match (true) {
            $exception instanceof SettingValueOutOfRangeException => new HttpException(
                statusCode: 422,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            default => null,
        };
    }
}
