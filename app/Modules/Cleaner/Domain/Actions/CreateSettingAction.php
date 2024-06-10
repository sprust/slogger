<?php

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Domain\Actions\Interfaces\CreateSettingActionInterface;
use App\Modules\Cleaner\Domain\Entities\Objects\SettingObject;
use App\Modules\Cleaner\Domain\Exceptions\SettingAlreadyExistsException;
use App\Modules\Cleaner\Repositories\Interfaces\SettingRepositoryInterface;

readonly class CreateSettingAction implements CreateSettingActionInterface
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
