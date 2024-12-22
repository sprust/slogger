<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Entities\Trace\TraceServicesObject;

interface FindTraceServicesActionInterface
{
    /**
     * @param int[]|null $serviceIds
     */
    public function handle(?array $serviceIds = null): TraceServicesObject;
}
