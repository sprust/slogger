<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Exceptions;

use App\Modules\Common\Infrastructure\Exceptions\ExceptionManagerInterface;
use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Watcher\Domain\Exceptions\WatcherIncidentNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

readonly class WatcherExceptionManager implements ExceptionManagerInterface
{
    public function map(Throwable $exception): Response|HttpException|null
    {
        return match (true) {
            $exception instanceof WatcherNotFoundException => new HttpException(
                statusCode: 404,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            $exception instanceof WatcherIncidentNotFoundException => new HttpException(
                statusCode: 404,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            default => null,
        };
    }
}
