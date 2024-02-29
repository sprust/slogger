<?php

namespace App\Modules\TracesAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\TracesAggregator\Dto\Objects\TraceTreeNodeObjects;
use App\Modules\TracesAggregator\Dto\Parameters\TraceMapFindParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceTreeInsertParameters;

interface TraceTreeRepositoryInterface
{
    /**
     * @param TraceTreeInsertParameters[] $parametersList
     */
    public function insertMany(array $parametersList): void;

    public function findTraces(TraceMapFindParameters $parameters): TraceTreeNodeObjects;

    public function findTraceIdsInTreeByParentTraceId(Trace $parentTrace): array;
}
