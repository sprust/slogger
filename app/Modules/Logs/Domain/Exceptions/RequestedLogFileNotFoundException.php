<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Exceptions;

use Exception;

class RequestedLogFileNotFoundException extends Exception
{
    public function __construct(public readonly string $fileId)
    {
        parent::__construct("Log file [$fileId] not found");
    }
}
