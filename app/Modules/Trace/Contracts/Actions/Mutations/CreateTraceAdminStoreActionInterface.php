<?php

namespace App\Modules\Trace\Contracts\Actions\Mutations;

use App\Modules\Trace\Entities\Store\TraceAdminStoreObject;

interface CreateTraceAdminStoreActionInterface
{
    public function handle(
        string $title,
        int $storeVersion,
        string $storeData,
        bool $auto
    ): TraceAdminStoreObject;
}
