<?php

namespace App\Modules\Trace\Repositories\Interfaces;

use App\Modules\Trace\Repositories\Dto\TraceAdminStoreDto;
use App\Modules\Trace\Repositories\Dto\TraceAdminStoresPaginationDto;
use Illuminate\Support\Carbon;

interface TraceAdminStoreRepositoryInterface
{
    public function create(
        string $title,
        int $storeVersion,
        string $storeDataHash,
        string $storeData,
        int $creatorId,
        ?Carbon $usedAt
    ): TraceAdminStoreDto;

    public function find(
        int $page,
        int $perPage,
        int $version,
        ?string $searchQuery = null
    ): TraceAdminStoresPaginationDto;

    public function delete(string $id): bool;
}
