<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Exceptions;

use App\Modules\Watcher\Domain\Exceptions\AbstractWatcherException;
use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Watcher\Domain\Exceptions\WatcherIncidentNotFoundException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class WatcherExceptionManager extends BaseExceptionManager
{
    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject
    {
        $exception = $this->detectException($exception);

        if (!$this->isHandlingException($exception)) {
            return null;
        }

        $httpStatus = match (true) {
            $exception instanceof WatcherNotFoundException         => SymfonyResponse::HTTP_NOT_FOUND,
            $exception instanceof WatcherIncidentNotFoundException => SymfonyResponse::HTTP_NOT_FOUND,
            default                                                => SymfonyResponse::HTTP_BAD_REQUEST,
        };

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: $httpStatus,
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof AbstractWatcherException;
    }

    protected function getDontReport(): array
    {
        return [
            WatcherNotFoundException::class,
            WatcherIncidentNotFoundException::class,
        ];
    }

    protected function onMakeMessage(Throwable $exception): string
    {
        if ($exception instanceof WatcherNotFoundException) {
            return $exception->getMessage();
        }

        if ($exception instanceof WatcherIncidentNotFoundException) {
            return $exception->getMessage();
        }

        return 'Unknown error';
    }
}
