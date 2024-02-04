<?php

namespace App\Modules\TracesAggregator\Repositories;

use App\Modules\TracesAggregator\Dto\Objects\TraceChildObjects;
use App\Modules\TracesAggregator\Dto\Parameters\TraceChildrenFindParameters;

interface TraceChildrenRepositoryInterface
{
    public function find(TraceChildrenFindParameters $parameters): TraceChildObjects;
}
