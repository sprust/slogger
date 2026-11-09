<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Exceptions;

use App\Modules\Common\Infrastructure\Exceptions\ExceptionManagerInterface;
use App\Modules\Dashboard\Domain\Exceptions\DatabaseStatCacheNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

readonly class DashboardExceptionManager implements ExceptionManagerInterface
{
    public function map(Throwable $exception): Response|HttpException|null
    {
        return match (true) {
            $exception instanceof DatabaseStatCacheNotFoundException => new JsonResponse(
                data: [
                    'error' => 'No data yet. Please wait for the first cache refresh.',
                ],
                status: 503,
            ),
            default => null,
        };
    }
}
