<?php

namespace App\Modules\TraceCleaner\Domain\Actions;

use App\Modules\TraceCleaner\Repositories\Interfaces\SettingRepositoryInterface;

readonly class DeleteSettingAction
{
    public function __construct(
        private SettingRepositoryInterface $settingRepository
    ) {
    }

    public function handle(int $settingId): bool
    {
        return $this->settingRepository->delete($settingId);
    }
}
