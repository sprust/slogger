<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

use App\Modules\Trace\Domain\Entities\Objects\TraceAdminStoreObject;

interface CreateTraceAdminStoreActionInterface
{
    public function handle(
        string $title,
        int $storeVersion,
        string $storeData,
    ): TraceAdminStoreObject;
}
