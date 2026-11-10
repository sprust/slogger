<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Exceptions;

use App\Modules\Notification\Domain\Exceptions\AbstractNotificationException;
use App\Modules\Notification\Domain\Exceptions\NotificationChannelNotFoundException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class NotificationExceptionManager extends BaseExceptionManager
{
    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject
    {
        $exception = $this->detectException($exception);

        if (!$this->isHandlingException($exception)) {
            return null;
        }

        $httpStatus = match (true) {
            $exception instanceof NotificationChannelNotFoundException => SymfonyResponse::HTTP_NOT_FOUND,
            default                                                    => SymfonyResponse::HTTP_BAD_REQUEST,
        };

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: $httpStatus,
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof AbstractNotificationException;
    }

    protected function getDontReport(): array
    {
        return [NotificationChannelNotFoundException::class];
    }

    protected function onMakeMessage(Throwable $exception): string
    {
        if ($exception instanceof NotificationChannelNotFoundException) {
            return $exception->getMessage();
        }

        return 'Unknown error';
    }
}
