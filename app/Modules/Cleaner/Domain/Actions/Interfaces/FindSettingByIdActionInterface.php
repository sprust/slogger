<?php

namespace App\Modules\Cleaner\Domain\Actions\Interfaces;

use App\Modules\Cleaner\Domain\Entities\Objects\SettingObject;

interface FindSettingByIdActionInterface
{
    public function handle(int $settingId): ?SettingObject;
}
