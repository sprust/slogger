<?php

namespace App\Modules\TracesAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\TracesAggregator\Dto\Objects\TraceTreeNodeObjects;
use App\Modules\TracesAggregator\Dto\Parameters\TraceFindTreeParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceTreeDeleteManyParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceTreeInsertParameters;

interface TraceTreeRepositoryInterface
{
    /**
     * @param TraceTreeInsertParameters[] $parametersList
     */
    public function insertMany(array $parametersList): void;

    public function findTraces(TraceFindTreeParameters $parameters): TraceTreeNodeObjects;

    public function findTraceIdsInTreeByParentTraceId(Trace $parentTrace): array;

    public function deleteMany(TraceTreeDeleteManyParameters $parameters): void;
}
