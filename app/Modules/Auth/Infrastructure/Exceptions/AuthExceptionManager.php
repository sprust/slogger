<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Exceptions;

use App\Modules\Auth\Domain\Exceptions\AuthenticationFailedException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class AuthExceptionManager extends BaseExceptionManager
{
    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject
    {
        $exception = $this->detectException($exception);

        if (!$this->isHandlingException($exception)) {
            return null;
        }

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: SymfonyResponse::HTTP_UNAUTHORIZED,
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof AuthenticationFailedException;
    }

    protected function getDontReport(): array
    {
        return [
            AuthenticationFailedException::class,
        ];
    }

    protected function onMakeMessage(Throwable $exception): string
    {
        if ($exception instanceof AuthenticationFailedException) {
            return 'Authentication failed';
        }

        return 'Forbidden';
    }
}
