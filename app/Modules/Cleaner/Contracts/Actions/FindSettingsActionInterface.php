<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Contracts\Actions;

use App\Modules\Cleaner\Entities\SettingObject;

interface FindSettingsActionInterface
{
    /**
     * @return SettingObject[]
     */
    public function handle(): array;
}
