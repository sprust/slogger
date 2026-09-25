<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Exceptions;

use RuntimeException;

class LogCursorInvalidException extends RuntimeException
{
    public function __construct(string $reason)
    {
        parent::__construct("Logs cursor is invalid: $reason");
    }
}
