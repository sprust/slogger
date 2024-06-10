<?php

namespace App\Modules\Cleaner\Domain\Actions\Interfaces;

interface DeleteSettingActionInterface
{
    public function handle(int $settingId): bool;
}
