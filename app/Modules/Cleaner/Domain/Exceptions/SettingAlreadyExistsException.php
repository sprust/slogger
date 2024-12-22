<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Exceptions;

use Exception;

class SettingAlreadyExistsException extends Exception
{
    public function __construct(?string $type)
    {
        parent::__construct("Setting for type [$type] already exists");
    }
}
