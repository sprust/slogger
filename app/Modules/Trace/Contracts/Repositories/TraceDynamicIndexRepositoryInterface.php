<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexDto;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexDataDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDynamicIndexStatsDto;
use Illuminate\Support\Carbon;
use Throwable;

interface TraceDynamicIndexRepositoryInterface
{
    public function findOneOrCreate(TraceDynamicIndexDataDto $indexData, Carbon $actualUntilAt): ?TraceDynamicIndexDto;

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

    public function updateByName(string $name, bool $inProcess, bool $created, ?Throwable $exception): bool;

    public function deleteById(string $id): bool;
}
