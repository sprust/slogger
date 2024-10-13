<?php

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Contracts\Actions\DeleteSettingActionInterface;
use App\Modules\Cleaner\Contracts\Repositories\SettingRepositoryInterface;

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
