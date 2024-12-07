<?php

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Entities\Trace\TraceServicesObject;

interface FindTraceServicesActionInterface
{
    public function handle(?array $serviceIds = null): TraceServicesObject;
}
