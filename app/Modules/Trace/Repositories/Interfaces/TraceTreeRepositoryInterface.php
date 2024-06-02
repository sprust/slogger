<?php

namespace App\Modules\Trace\Repositories\Interfaces;

interface TraceTreeRepositoryInterface
{
    public function findParentTraceId(string $traceId): ?string;

    /**
     * @return string[]
     */
    public function findTraceIdsInTreeByParentTraceId(string $traceId): array;
}
