<?php

namespace App\Modules\TraceCleaner\Repositories\Contracts;

use App\Modules\TraceCleaner\Repositories\Dto\SettingDto;

interface SettingRepositoryInterface
{
    /**
     * @return SettingDto[]
     */
    public function find(?string $type, ?bool $typeIsNotNull = null): array;

    public function findOneById(int $id): ?SettingDto;

    public function create(int $daysLifetime, ?string $type): int;

    public function update(int $id, int $daysLifetime, ?string $type): bool;

    public function delete(int $id): void;
}
