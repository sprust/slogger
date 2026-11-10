<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Exceptions;

class RequestedLogFileNotFoundException extends AbstractLogsException
{
    public function __construct(public readonly string $fileId)
    {
        parent::__construct("Log file [$fileId] not found");
    }
}
