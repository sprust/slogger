<?php

namespace App\Modules\TracesAggregator\Repositories;

use App\Modules\TracesAggregator\Dto\Objects\TraceParentObjects;
use App\Modules\TracesAggregator\Dto\Objects\TraceParentTypeObjects;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentTypesParameters;

interface TraceParentsRepositoryInterface
{
    public function findParentTypes(TraceParentTypesParameters $parameters): TraceParentTypeObjects;

    public function findParents(TraceParentsFindParameters $parameters): TraceParentObjects;
}
