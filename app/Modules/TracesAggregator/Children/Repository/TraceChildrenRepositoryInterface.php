<?php

namespace App\Modules\TracesAggregator\Children\Repository;

use App\Modules\TracesAggregator\Children\Dto\Objects\TraceChildObjects;
use App\Modules\TracesAggregator\Children\Dto\Parameters\TraceChildrenFindParameters;

interface TraceChildrenRepositoryInterface
{
    public function find(TraceChildrenFindParameters $parameters): TraceChildObjects;
}
