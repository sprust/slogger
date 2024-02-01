<?php

namespace App\Modules\TracesAggregator\Parents\Repository;

use App\Modules\TracesAggregator\Parents\Dto\Objects\TraceParentObjects;
use App\Modules\TracesAggregator\Parents\Dto\Objects\TraceParentTypeObjects;
use App\Modules\TracesAggregator\Parents\Dto\Parameters\TraceParentsFindParameters;
use App\Modules\TracesAggregator\Parents\Dto\Parameters\TraceParentTypesParameters;

interface TraceParentsRepositoryInterface
{
    public function findParentTypes(TraceParentTypesParameters $parameters): TraceParentTypeObjects;

    public function findParents(TraceParentsFindParameters $parameters): TraceParentObjects;
}
