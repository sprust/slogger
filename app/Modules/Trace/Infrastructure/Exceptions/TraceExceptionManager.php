<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Exceptions;

use App\Modules\Common\Infrastructure\Exceptions\ExceptionManagerInterface;
use App\Modules\Trace\Domain\Exceptions\TraceNotFoundException;
use App\Modules\Trace\Domain\Exceptions\TraceTreeStateNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

readonly class TraceExceptionManager implements ExceptionManagerInterface
{
    public function map(Throwable $exception): Response|HttpException|null
    {
        return match (true) {
            $exception instanceof TraceNotFoundException => new HttpException(
                statusCode: 404,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            $exception instanceof TraceTreeStateNotFoundException => new HttpException(
                statusCode: 404,
                message: $exception->getMessage(),
                previous: $exception,
            ),
            default => null,
        };
    }
}
