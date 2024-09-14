<?php

namespace App\Modules\Trace\Repositories\Interfaces;

use App\Modules\Trace\Repositories\Dto\TraceDynamicIndexDto;
use App\Modules\Trace\Repositories\Dto\TraceDynamicIndexFieldDto;
use Illuminate\Support\Carbon;
use Throwable;

interface TraceDynamicIndexRepositoryInterface
{
    /**
     * @param TraceDynamicIndexFieldDto[] $fields
     */
    public function findOneOrCreate(array $fields, Carbon $actualUntilAt): ?TraceDynamicIndexDto;

    /**
     * @return TraceDynamicIndexDto[]
     */
    public function find(
        int $limit,
        ?bool $inProcess = null,
        bool $sortByCreatedAtAsc = false,
        ?Carbon $toActualUntilAt = null
    ): array;

    public function updateByName(string $name, bool $inProcess, bool $created, ?Throwable $exception): bool;

    public function deleteByName(string $name): bool;
}
