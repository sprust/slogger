<?php

namespace App\Modules\TraceAggregator\Repositories\Interfaces;

use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceTreeDeleteManyParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceTreeInsertParameters;

interface TraceTreeRepositoryInterface
{
    /**
     * @param TraceTreeInsertParameters[] $parametersList
     */
    public function insertMany(array $parametersList): void;

    public function findParentTraceId(string $traceId): ?string;

    /**
     * @return string[]
     */
    public function findTraceIdsInTreeByParentTraceId(string $traceId): array;

    public function deleteMany(TraceTreeDeleteManyParameters $parameters): void;
}
