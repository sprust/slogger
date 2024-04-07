<?php

namespace App\Modules\TraceCleaner\Domain\Exceptions;

use Exception;

class SettingAlreadyExistsException extends Exception
{
    public function __construct(?string $type)
    {
        parent::__construct("Setting for type [$type] already exists");
    }
}
