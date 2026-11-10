<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Exceptions\Manager;

use Throwable;

interface ExceptionManagerInterface
{
    public function isReportable(Throwable $exception): ?bool;

    public function makeJsonHttpResponse(Throwable $exception): ?JsonHttpExceptionResponseObject;
}
