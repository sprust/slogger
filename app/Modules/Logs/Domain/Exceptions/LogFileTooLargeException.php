<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Exceptions;

use RuntimeException;

class LogFileTooLargeException extends RuntimeException
{
    public function __construct(string $name, int $sizeBytes, int $maxBytes)
    {
        $sizeMb = round($sizeBytes / 1024 / 1024, 1);
        $maxMb  = round($maxBytes / 1024 / 1024, 1);

        parent::__construct("Log file [$name] is $sizeMb MB, too large to download (at most $maxMb MB)");
    }
}
