<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Exceptions;

use App\Modules\User\Domain\Exceptions\AbstractUserException;
use App\Modules\User\Domain\Exceptions\UserAlreadyExistsException;
use App\Modules\User\Domain\Exceptions\UserNotFoundException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
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
            $exception instanceof UserAlreadyExistsException => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
            $exception instanceof UserNotFoundException      => SymfonyResponse::HTTP_NOT_FOUND,
            default                                          => SymfonyResponse::HTTP_BAD_REQUEST,
        };

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: $httpStatus,
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof AbstractUserException;
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
        if ($exception instanceof UserAlreadyExistsException) {
            return $exception->getMessage();
        }

        if ($exception instanceof UserNotFoundException) {
            return $exception->getMessage();
        }

        return 'Unknown error';
    }
}
