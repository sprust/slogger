<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Contracts\Actions;

interface FindMaxDaysSettingActionInterface
{
    public function handle(): ?int;
}
