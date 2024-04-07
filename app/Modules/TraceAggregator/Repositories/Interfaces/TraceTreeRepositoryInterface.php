<?php

namespace App\Modules\TraceAggregator\Repositories\Interfaces;

interface TraceTreeRepositoryInterface
{
    public function findParentTraceId(string $traceId): ?string;

    /**
     * @return string[]
     */
    public function findTraceIdsInTreeByParentTraceId(string $traceId): array;
}
