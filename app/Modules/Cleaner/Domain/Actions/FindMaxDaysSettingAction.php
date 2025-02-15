<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Contracts\Actions\FindMaxDaysSettingActionInterface;
use App\Modules\Cleaner\Contracts\Repositories\SettingRepositoryInterface;

readonly class FindMaxDaysSettingAction implements FindMaxDaysSettingActionInterface
{
    public function __construct(
        private SettingRepositoryInterface $settingRepository
    ) {
    }

    public function handle(): ?int
    {
        return $this->settingRepository->findMaxDay();
    }
}
