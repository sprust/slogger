<?php

namespace App\Modules\TraceAggregator\Repositories\Interfaces;

use App\Models\Traces\Trace;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTreeObjects;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindTreeParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceTreeDeleteManyParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceTreeInsertParameters;
use App\Modules\TraceAggregator\Domain\Exceptions\TreeTooLongException;

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
