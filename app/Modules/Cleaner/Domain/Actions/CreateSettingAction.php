<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Contracts\Actions\CreateSettingActionInterface;
use App\Modules\Cleaner\Contracts\Repositories\SettingRepositoryInterface;
use App\Modules\Cleaner\Domain\Exceptions\SettingAlreadyExistsException;
use App\Modules\Cleaner\Entities\SettingObject;

readonly class CreateSettingAction implements CreateSettingActionInterface
{
    public function __construct(
        private FindSettingByIdAction $findSettingByIdAction,
        private SettingRepositoryInterface $settingRepository
    ) {
    }

    public function handle(int $daysLifetime, ?string $type, bool $onlyData): SettingObject
    {
        $existSettings = $this->settingRepository->find(
            type: $type,
            typeIsNotNull: !is_null($type),
            onlyData: $onlyData
        );

        if ($existSettings) {
            throw new SettingAlreadyExistsException($type);
        }

        $settingId = $this->settingRepository->create(
            daysLifetime: $daysLifetime,
            type: $type,
            onlyData: $onlyData
        );

        return $this->findSettingByIdAction->handle($settingId);
    }
}
