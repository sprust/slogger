<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Objects\TraceAdminStoresPaginationObject;

interface FindTraceAdminStoreActionInterface
{
    public function handle(int $page, int $version, ?string $searchQuery = null): TraceAdminStoresPaginationObject;
}
