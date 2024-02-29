<?php

namespace App\Modules\TracesAggregator\Repositories;

use App\Modules\TracesAggregator\Dto\Objects\TraceParentObjects;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindStatusesParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindTypesParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindTagsParameters;
use App\Modules\TracesAggregator\Dto\TraceDetailObject;

interface TraceParentsRepositoryInterface
{
    public function findByTraceId(string $traceId): ?TraceDetailObject;

    public function findParents(TraceParentsFindParameters $parameters): TraceParentObjects;

    public function findTypes(TraceParentsFindTypesParameters $parameters): array;

    public function findTags(TraceParentsFindTagsParameters $parameters): array;

    public function findStatuses(TraceParentsFindStatusesParameters $parameters): array;
}
