<?php

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Repositories\Dto\TraceAdminStoreDto;
use App\Modules\Trace\Repositories\Dto\TraceAdminStoresPaginationDto;

interface TraceAdminStoreRepositoryInterface
{
    public function create(
        string $title,
        int $storeVersion,
        string $storeDataHash,
        string $storeData
    ): TraceAdminStoreDto;

    public function find(
        int $page,
        int $perPage,
        int $version,
        ?string $searchQuery = null
    ): TraceAdminStoresPaginationDto;

    public function delete(string $id): bool;
}
