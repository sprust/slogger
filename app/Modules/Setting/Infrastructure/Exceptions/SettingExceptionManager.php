<?php

declare(strict_types=1);

namespace App\Modules\Setting\Infrastructure\Exceptions;

use App\Modules\Setting\Domain\Exceptions\AbstractSettingException;
use App\Modules\Setting\Domain\Exceptions\SettingValueOutOfRangeException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class SettingExceptionManager extends BaseExceptionManager
{
    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject
    {
        $exception = $this->detectException($exception);

        if (!$this->isHandlingException($exception)) {
            return null;
        }

        $httpStatus = match (true) {
            $exception instanceof SettingValueOutOfRangeException => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
            default                                               => SymfonyResponse::HTTP_BAD_REQUEST,
        };

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: $httpStatus,
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof AbstractSettingException;
    }

    protected function getDontReport(): array
    {
        return [SettingValueOutOfRangeException::class];
    }

    protected function onMakeMessage(Throwable $exception): string
    {
        if ($exception instanceof SettingValueOutOfRangeException) {
            return $exception->getMessage();
        }

        return 'Unknown error';
    }
}
