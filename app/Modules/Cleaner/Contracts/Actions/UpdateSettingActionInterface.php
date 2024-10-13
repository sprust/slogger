<?php

namespace App\Modules\Cleaner\Contracts\Actions;

use App\Modules\Cleaner\Domain\Exceptions\SettingAlreadyExistsException;
use App\Modules\Cleaner\Domain\Exceptions\SettingNotFoundException;
use App\Modules\Cleaner\Entities\SettingObject;

interface UpdateSettingActionInterface
{
    /**
     * @throws SettingNotFoundException
     * @throws SettingAlreadyExistsException
     */
    public function handle(int $settingId, int $daysLifetime, bool $onlyData): SettingObject;
}
