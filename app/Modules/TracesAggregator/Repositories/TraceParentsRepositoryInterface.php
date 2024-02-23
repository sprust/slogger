<?php

namespace App\Modules\TracesAggregator\Repositories;

use App\Modules\TracesAggregator\Dto\Objects\TraceParentObject;
use App\Modules\TracesAggregator\Dto\Objects\TraceParentObjects;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindByTextParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindParameters;

interface TraceParentsRepositoryInterface
{
    public function findByTraceId(string $traceId): ?TraceParentObject;

    public function findParents(TraceParentsFindParameters $parameters): TraceParentObjects;

    public function findTypes(TraceParentsFindByTextParameters $parameters): array;

    public function findTags(TraceParentsFindByTextParameters $parameters): array;
}
