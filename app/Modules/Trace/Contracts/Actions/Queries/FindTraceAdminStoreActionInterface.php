<?php

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Entities\Store\TraceAdminStoresPaginationObject;

interface FindTraceAdminStoreActionInterface
{
    public function handle(int $page, int $version, ?string $searchQuery = null): TraceAdminStoresPaginationObject;
}
