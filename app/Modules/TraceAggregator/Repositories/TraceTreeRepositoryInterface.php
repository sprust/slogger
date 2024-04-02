<?php

namespace App\Modules\TraceAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\TraceAggregator\Dto\Objects\TraceTreeObjects;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindTreeParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceTreeDeleteManyParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceTreeInsertParameters;
use App\Modules\TraceAggregator\Exceptions\TreeTooLongException;

interface TraceTreeRepositoryInterface
{
    /**
     * @param TraceTreeInsertParameters[] $parametersList
     */
    public function insertMany(array $parametersList): void;

    /**
     * @throws TreeTooLongException
     */
    public function find(TraceFindTreeParameters $parameters): TraceTreeObjects;

    public function findTraceIdsInTreeByParentTraceId(Trace $parentTrace): array;

    public function deleteMany(TraceTreeDeleteManyParameters $parameters): void;
}
