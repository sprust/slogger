<?php

namespace App\Modules\Trace\Repositories\Interfaces;

use App\Modules\Trace\Repositories\Dto\SettingDto;

interface SettingRepositoryInterface
{
    /**
     * @return SettingDto[]
     */
    public function find(?string $type, ?bool $typeIsNotNull = null, ?bool $deleted = null): array;

    public function findOneById(int $id): ?SettingDto;

    public function create(int $daysLifetime, ?string $type): int;

    public function update(int $id, int $daysLifetime): bool;

    public function delete(int $id): bool;
}
