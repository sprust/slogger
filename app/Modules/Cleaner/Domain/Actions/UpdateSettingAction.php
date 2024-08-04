<?php

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Domain\Actions\Interfaces\UpdateSettingActionInterface;
use App\Modules\Cleaner\Domain\Entities\Objects\SettingObject;
use App\Modules\Cleaner\Domain\Exceptions\SettingAlreadyExistsException;
use App\Modules\Cleaner\Domain\Exceptions\SettingNotFoundException;
use App\Modules\Cleaner\Repositories\Interfaces\SettingRepositoryInterface;

readonly class UpdateSettingAction implements UpdateSettingActionInterface
{
    public function __construct(
        private FindSettingByIdAction $findSettingByIdAction,
        private SettingRepositoryInterface $settingRepository
    ) {
    }

    public function handle(int $settingId, int $daysLifetime, bool $onlyData): SettingObject
    {
        $setting = $this->settingRepository->findOneById($settingId);

        if (!$setting) {
            throw new SettingNotFoundException($settingId);
        }

        $existSettings = $this->settingRepository->find(
            type: $setting->type,
            typeIsNotNull: !is_null($setting->type),
            onlyData: $onlyData,
            excludeId: $setting->id
        );

        if ($existSettings) {
            throw new SettingAlreadyExistsException($setting->type);
        }

        $this->settingRepository->update(
            id: $settingId,
            daysLifetime: $daysLifetime,
            onlyData: $onlyData
        );

        return $this->findSettingByIdAction->handle($settingId);
    }
}
