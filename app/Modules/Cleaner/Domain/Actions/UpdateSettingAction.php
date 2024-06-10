<?php

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Domain\Actions\Interfaces\UpdateSettingActionInterface;
use App\Modules\Cleaner\Domain\Entities\Objects\SettingObject;
use App\Modules\Cleaner\Domain\Exceptions\SettingNotFoundException;
use App\Modules\Cleaner\Repositories\Interfaces\SettingRepositoryInterface;

readonly class UpdateSettingAction implements UpdateSettingActionInterface
{
    public function __construct(
        private FindSettingByIdAction $findSettingByIdAction,
        private SettingRepositoryInterface $settingRepository
    ) {
    }

    /**
     * @throws SettingNotFoundException
     */
    public function handle(int $settingId, int $daysLifetime): SettingObject
    {
        if (!$this->settingRepository->findOneById($settingId)) {
            throw new SettingNotFoundException($settingId);
        }

        $this->settingRepository->update(
            id: $settingId,
            daysLifetime: $daysLifetime,
        );

        return $this->findSettingByIdAction->handle($settingId);
    }
}
