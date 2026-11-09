<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Exceptions;

use App\Modules\Dashboard\Domain\Exceptions\DatabaseStatCacheNotFoundException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use Throwable;

class DashboardExceptionManager extends BaseExceptionManager
{
    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject
    {
        $exception = $this->detectException($exception);

        if (!$this->isHandlingException($exception)) {
            return null;
        }

        $httpStatus = 503;

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: $httpStatus,
            messageField: 'error',
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof DatabaseStatCacheNotFoundException;
    }

    protected function getDontReport(): array
    {
        return [DatabaseStatCacheNotFoundException::class];
    }

    protected function onMakeMessage(Throwable $exception): string
    {
        return 'No data yet. Please wait for the first cache refresh.';
    }
}
