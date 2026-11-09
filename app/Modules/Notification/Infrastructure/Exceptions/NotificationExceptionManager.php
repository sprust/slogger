<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Exceptions;

use App\Modules\Common\Infrastructure\Exceptions\ExceptionManagerInterface;
use App\Modules\Notification\Domain\Exceptions\NotificationChannelNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

readonly class NotificationExceptionManager implements ExceptionManagerInterface
{
    public function map(Throwable $exception): Response|HttpException|null
    {
        return match (true) {
            $exception instanceof NotificationChannelNotFoundException => new HttpException(
                statusCode: 404,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            default => null,
        };
    }
}
