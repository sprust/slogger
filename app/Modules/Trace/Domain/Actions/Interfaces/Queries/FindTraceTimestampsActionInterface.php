<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampsObjects;
use App\Modules\Trace\Domain\Entities\Parameters\FindTraceTimestampsParameters;

interface FindTraceTimestampsActionInterface
{
    public function handle(FindTraceTimestampsParameters $parameters): TraceTimestampsObjects;
}
