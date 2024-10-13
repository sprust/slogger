<?php

namespace App\Modules\Trace\Contracts\Repositories;

interface TraceTreeRepositoryInterface
{
    public function findParentTraceId(string $traceId): ?string;

    /**
     * @return string[]
     */
    public function findTraceIdsInTreeByParentTraceId(string $traceId): array;
}
