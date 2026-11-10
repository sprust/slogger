<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Exceptions;

use App\Modules\Common\Domain\Exceptions\AbstractCommonException;
use App\Modules\Common\Infrastructure\Exceptions\Manager\BaseExceptionManager;
use App\Modules\Common\Infrastructure\Exceptions\Manager\JsonHttpExceptionResponseObject;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class CommonExceptionManager extends BaseExceptionManager
{
    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject
    {
        $exception = $this->detectException($exception);

        if (!$this->isHandlingException($exception)) {
            return null;
        }

        return new JsonHttpExceptionResponseObject(
            message: $this->onMakeMessage($exception),
            httpStatus: SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR,
        );
    }

    protected function isHandlingException(Throwable $exception): bool
    {
        return $exception instanceof AbstractCommonException;
    }

    protected function getDontReport(): array
    {
        return [];
    }

    protected function onMakeMessage(Throwable $exception): string
    {
        return 'Server Error';
    }
}
