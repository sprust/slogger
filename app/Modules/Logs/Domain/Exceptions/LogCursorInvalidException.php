<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Exceptions;

class LogCursorInvalidException extends AbstractLogsException
{
    public function __construct(string $reason)
    {
        parent::__construct("Logs cursor is invalid: $reason");
    }
}
