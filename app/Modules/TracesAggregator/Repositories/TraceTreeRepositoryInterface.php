<?php

namespace App\Modules\TracesAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\TracesAggregator\Dto\Objects\TraceTreeNodeObjects;
use App\Modules\TracesAggregator\Dto\Parameters\TraceMapFindParameters;

interface TraceTreeRepositoryInterface
{
    public function findTraces(TraceMapFindParameters $parameters): TraceTreeNodeObjects;

    public function findTraceIdsInTreeByParentTraceId(Trace $parentTrace): array;
}