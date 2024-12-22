<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Contracts\Actions\UpdateSettingActionInterface;
use App\Modules\Cleaner\Contracts\Repositories\SettingRepositoryInterface;
use App\Modules\Cleaner\Domain\Exceptions\SettingAlreadyExistsException;
use App\Modules\Cleaner\Domain\Exceptions\SettingNotFoundException;
use App\Modules\Cleaner\Entities\SettingObject;

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
