<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Contracts\Actions;

interface DeleteSettingActionInterface
{
    public function handle(int $settingId): bool;
}
