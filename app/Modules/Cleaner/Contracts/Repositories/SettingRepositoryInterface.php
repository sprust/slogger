<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Contracts\Repositories;

use App\Modules\Cleaner\Entities\SettingObject;

interface SettingRepositoryInterface
{
    /**
     * @return SettingObject[]
     */
    public function find(
        ?string $type = null,
        ?bool $typeIsNotNull = null,
        ?bool $onlyData = null,
        ?int $excludeId = null,
        ?bool $deleted = null,
        bool $orderByTypeAndOnlyData = false
    ): array;

    public function findOneById(int $id): ?SettingObject;

    public function findMaxDay(): ?int;

    public function create(int $daysLifetime, ?string $type, bool $onlyData): int;

    public function update(int $id, int $daysLifetime, bool $onlyData): bool;

    public function delete(int $id): bool;
}
