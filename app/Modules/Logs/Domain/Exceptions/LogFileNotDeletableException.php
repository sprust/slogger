<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Exceptions;

class LogFileNotDeletableException extends AbstractLogsException
{
    public function __construct(string $name)
    {
        parent::__construct(
            "Log file [$name] can not be deleted: its source does not allow it, or it is the newest file of the source"
        );
    }
}
