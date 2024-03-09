<?php

namespace App\Modules\TraceCleaner\Services;

use App\Modules\TraceCleaner\Repositories\Contracts\SettingRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Dto\SettingDto;
use App\Modules\TraceCleaner\Services\Objects\SettingObject;

readonly class SettingService
{
    public function __construct(
        private SettingRepositoryInterface $settingRepository
    ) {
    }

    /**
     * @return SettingObject[]
     */
    public function find(): array
    {
        $settings = $this->settingRepository->find();

        return array_map(
            fn(SettingDto $settingDto) => new SettingObject(
                id: $settingDto->id,
                daysLifetime: $settingDto->daysLifetime,
                type: $settingDto->type,
                createdAt: $settingDto->createdAt,
                updatedAt: $settingDto->updatedAt
            ),
            $settings
        );
    }

    public function findOneById(int $settingId): ?SettingObject
    {
        $setting = $this->settingRepository->findOneById($settingId);

        if (!$setting) {
            return null;
        }

        return new SettingObject(
            id: $setting->id,
            daysLifetime: $setting->daysLifetime,
            type: $setting->type,
            createdAt: $setting->createdAt,
            updatedAt: $setting->updatedAt
        );
    }

    public function createOrUpdate(int $daysLifetime, ?string $type): SettingObject
    {
        $existSettings = $this->settingRepository->find(
            type: $type,
            typeIsNotNull: !is_null($type)
        );

        if (empty($existSettings)) {
            $settingId = $this->settingRepository->create(
                daysLifetime: $daysLifetime,
                type: $type
            );
        } else {
            $existsSettingDto = $existSettings[0];

            $this->settingRepository->update(
                id: $existsSettingDto->id,
                daysLifetime: $daysLifetime,
                type: $type
            );

            $settingId = $existsSettingDto->id;
        }

        $settingDto = $this->settingRepository->findOneById($settingId);

        return new SettingObject(
            id: $settingDto->id,
            daysLifetime: $settingDto->daysLifetime,
            type: $settingDto->type,
            createdAt: $settingDto->createdAt,
            updatedAt: $settingDto->updatedAt
        );
    }

    public function delete(int $settingId): void
    {
        $this->settingRepository->delete($settingId);
    }
}
