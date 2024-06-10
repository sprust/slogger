<?php

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Domain\Actions\Interfaces\DeleteSettingActionInterface;
use App\Modules\Cleaner\Repositories\Interfaces\SettingRepositoryInterface;

readonly class DeleteSettingAction implements DeleteSettingActionInterface
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
