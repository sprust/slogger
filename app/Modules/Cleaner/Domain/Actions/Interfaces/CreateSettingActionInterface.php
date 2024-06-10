<?php

namespace App\Modules\Cleaner\Domain\Actions\Interfaces;

use App\Modules\Cleaner\Domain\Entities\Objects\SettingObject;

interface CreateSettingActionInterface
{
    public function handle(int $daysLifetime, ?string $type): SettingObject;
}
