<?php

namespace App\Modules\TraceCleaner\Domain\Exceptions;

use Exception;

class SettingNotFoundException extends Exception
{
    public function __construct(?string $type)
    {
        parent::__construct("Setting for type [$type] not found");
    }
}
