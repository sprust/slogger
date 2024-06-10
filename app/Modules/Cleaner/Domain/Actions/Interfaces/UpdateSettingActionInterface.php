<?php

namespace App\Modules\Cleaner\Domain\Actions\Interfaces;

use App\Modules\Cleaner\Domain\Entities\Objects\SettingObject;
use App\Modules\Cleaner\Domain\Exceptions\SettingNotFoundException;

interface UpdateSettingActionInterface
{
    /**
     * @throws SettingNotFoundException
     */
    public function handle(int $settingId, int $daysLifetime): SettingObject;
}
