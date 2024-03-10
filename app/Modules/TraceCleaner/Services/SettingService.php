<?php

namespace App\Modules\TraceCleaner\Services;

use App\Modules\TraceCleaner\Exceptions\SettingAlreadyExistsException;
use App\Modules\TraceCleaner\Exceptions\SettingNotFoundException;
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

    /**
     * @throws SettingAlreadyExistsException
     */
    public function create(int $daysLifetime, ?string $type): SettingObject
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

        return $this->findOneById($settingId);
    }

    /**
     * @throws SettingNotFoundException
     */
    public function update(int $settingId, int $daysLifetime, ?string $type): SettingObject
    {
        if (!$this->settingRepository->findOneById($settingId)) {
            throw new SettingNotFoundException($type);
        }

        $this->settingRepository->update(
            id: $settingId,
            daysLifetime: $daysLifetime,
            type: $type
        );

        return $this->findOneById($settingId);
    }

    public function delete(int $settingId): void
    {
        $this->settingRepository->delete($settingId);
    }
}
