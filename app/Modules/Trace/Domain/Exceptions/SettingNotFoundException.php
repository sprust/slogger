<?php

namespace App\Modules\Trace\Domain\Exceptions;

use Exception;

class SettingNotFoundException extends Exception
{
    public function __construct(int $settingId)
    {
        parent::__construct("Setting by id [$settingId] not found");
    }
}
