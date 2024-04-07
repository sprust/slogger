<?php

namespace App\Modules\TraceCleaner\Domain\Actions;

use App\Modules\TraceCleaner\Domain\Entities\Objects\SettingObject;
use App\Modules\TraceCleaner\Domain\Exceptions\SettingAlreadyExistsException;
use App\Modules\TraceCleaner\Repositories\Interfaces\SettingRepositoryInterface;

readonly class CreateSettingAction
{
    public function __construct(
        private FindSettingByIdAction $findSettingByIdAction,
        private SettingRepositoryInterface $settingRepository
    ) {
    }

    /**
     * @throws SettingAlreadyExistsException
     */
    public function handle(int $daysLifetime, ?string $type): SettingObject
    {
        $existSettings = $this->settingRepository->find(
            type: $type,
            typeIsNotNull: !is_null($type)
        );

        if ($existSettings) {
            throw new SettingAlreadyExistsException($type);
        }

        $settingId = $this->settingRepository->create(
            daysLifetime: $daysLifetime,
            type: $type
        );

        return $this->findSettingByIdAction->handle($settingId);
    }
}
