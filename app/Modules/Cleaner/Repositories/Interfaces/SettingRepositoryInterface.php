<?php

namespace App\Modules\Cleaner\Repositories\Interfaces;

use App\Modules\Cleaner\Repositories\Dto\SettingDto;

interface SettingRepositoryInterface
{
    /**
     * @return SettingDto[]
     */
    public function find(
        ?string $type = null,
        ?bool $typeIsNotNull = null,
        ?bool $onlyData = null,
        ?int $excludeId = null,
        ?bool $deleted = null,
        bool $orderByTypeAndOnlyData = false
    ): array;

    public function findOneById(int $id): ?SettingDto;

    public function create(int $daysLifetime, ?string $type, bool $onlyData): int;

    public function update(int $id, int $daysLifetime, bool $onlyData): bool;

    public function delete(int $id): bool;
}
