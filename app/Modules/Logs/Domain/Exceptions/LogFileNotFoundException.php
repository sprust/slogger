<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Exceptions;

class LogFileNotFoundException extends AbstractLogsException
{
    public function __construct(string $path)
    {
        parent::__construct("Log file [$path] not found");
    }
}
