<?php

namespace App\Modules\TraceCleaner\Domain\Actions;

use App\Modules\TraceCleaner\Domain\Entities\Objects\SettingObject;
use App\Modules\TraceCleaner\Domain\Exceptions\SettingNotFoundException;
use App\Modules\TraceCleaner\Repositories\Interfaces\SettingRepositoryInterface;

readonly class UpdateSettingAction
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
