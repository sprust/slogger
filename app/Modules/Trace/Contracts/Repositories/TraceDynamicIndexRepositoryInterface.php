<?php

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexDto;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexesDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDynamicIndexStatsDto;
use Illuminate\Support\Carbon;
use Throwable;

interface TraceDynamicIndexRepositoryInterface
{
    public function findOneOrCreate(TraceDynamicIndexesDto $fields, Carbon $actualUntilAt): ?TraceDynamicIndexDto;

    public function findOneById(string $id): ?TraceDynamicIndexDto;

    /**
     * @return TraceDynamicIndexDto[]
     */
    public function find(
        int $limit,
        ?bool $inProcess = null,
        bool $sortByCreatedAtAsc = false,
        ?Carbon $toActualUntilAt = null,
        bool $orderByCreatedAtDesc = false,
    ): array;

    public function findStats(): TraceDynamicIndexStatsDto;

    public function updateById(string $id, bool $inProcess, bool $created, ?Throwable $exception): bool;

    public function deleteById(string $id): bool;
}
