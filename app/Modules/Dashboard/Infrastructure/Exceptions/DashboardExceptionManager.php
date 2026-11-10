<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Exceptions;

use App\Modules\Dashboard\Domain\Exceptions\AbstractDashboardException;
use App\Modules\Dashboard\Domain\Exceptions\DatabaseStatCacheNotFoundException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class DashboardExceptionManager extends BaseExceptionManager
{
    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject
    {
        $exception = $this->detectException($exception);

        if (!$this->isHandlingException($exception)) {
            return null;
        }

        $httpStatus = match (true) {
            $exception instanceof DatabaseStatCacheNotFoundException => SymfonyResponse::HTTP_SERVICE_UNAVAILABLE,
            default                                                  => SymfonyResponse::HTTP_BAD_REQUEST,
        };

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: $httpStatus,
            messageField: $exception instanceof DatabaseStatCacheNotFoundException ? 'error' : 'message',
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof AbstractDashboardException;
    }

    protected function getDontReport(): array
    {
        return [DatabaseStatCacheNotFoundException::class];
    }

    protected function onMakeMessage(Throwable $exception): string
    {
        if ($exception instanceof DatabaseStatCacheNotFoundException) {
            return 'No data yet. Please wait for the first cache refresh.';
        }

        return 'Unknown error';
    }
}
