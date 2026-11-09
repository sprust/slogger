<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Exceptions;

use App\Modules\User\Domain\Exceptions\UserAlreadyExistsException;
use App\Modules\User\Domain\Exceptions\UserNotFoundException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use Throwable;

class UserExceptionManager extends BaseExceptionManager
{
    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject
    {
        $exception = $this->detectException($exception);

        if (!$this->isHandlingException($exception)) {
            return null;
        }

        $httpStatus = match (true) {
            $exception instanceof UserAlreadyExistsException => 422,
            $exception instanceof UserNotFoundException      => 404,
            default                                          => 400,
        };

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: $httpStatus,
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof UserAlreadyExistsException
            || $exception instanceof UserNotFoundException;
    }

    protected function getDontReport(): array
    {
        return [
            UserAlreadyExistsException::class,
            UserNotFoundException::class,
        ];
    }

    protected function onMakeMessage(Throwable $exception): string
    {
        return $exception->getMessage();
    }
}
