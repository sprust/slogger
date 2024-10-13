<?php

namespace App\Modules\Cleaner\Contracts\Actions;

interface DeleteSettingActionInterface
{
    public function handle(int $settingId): bool;
}
