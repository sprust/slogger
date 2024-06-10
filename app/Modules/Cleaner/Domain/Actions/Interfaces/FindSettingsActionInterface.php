<?php

namespace App\Modules\Cleaner\Domain\Actions\Interfaces;

use App\Modules\Cleaner\Domain\Entities\Objects\SettingObject;

interface FindSettingsActionInterface
{
    /**
     * @return SettingObject[]
     */
    public function handle(): array;
}
