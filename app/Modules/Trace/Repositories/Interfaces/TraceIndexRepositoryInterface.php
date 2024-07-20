<?php

namespace App\Modules\Trace\Repositories\Interfaces;

use App\Modules\Trace\Repositories\Dto\TraceIndexDto;
use App\Modules\Trace\Repositories\Dto\TraceIndexFieldDto;
use Illuminate\Support\Carbon;

interface TraceIndexRepositoryInterface
{
    /**
     * @param TraceIndexFieldDto[] $fields
     */
    public function findOneOrCreate(array $fields, Carbon $actualUntilAt): ?TraceIndexDto;

    /**
     * @return TraceIndexDto[]
     */
    public function find(
        int $limit,
        ?bool $inProcess = null,
        bool $sortByCreatedAtAsc = false,
        ?Carbon $toActualUntilAt = null
    ): array;

    public function updateByName(string $name, bool $inProcess, bool $created): bool;

    public function deleteByName(string $name): bool;
}
