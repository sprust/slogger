<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Exceptions;

use Exception;

class SettingNotFoundException extends Exception
{
    public function __construct(int $settingId)
    {
        parent::__construct("Setting by id [$settingId] not found");
    }
}
