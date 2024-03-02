<?php

namespace App\Modules\TraceAggregator\Repositories;

use App\Modules\TraceAggregator\Dto\Objects\TraceDetailObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemObjects;
use App\Modules\TraceAggregator\Dto\Objects\TraceTreeShortObject;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindStatusesParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindTagsParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindTypesParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceTreeFindParameters;

interface TraceRepositoryInterface
{
    public function findByTraceId(string $traceId): ?TraceDetailObject;

    public function findParents(TraceFindParameters $parameters): TraceItemObjects;

    /**
     * @return string[]
     */
    public function findTypes(TraceFindTypesParameters $parameters): array;

    /**
     * @return string[]
     */
    public function findTags(TraceFindTagsParameters $parameters): array;

    /**
     * @return string[]
     */
    public function findStatuses(TraceFindStatusesParameters $parameters): array;

    /**
     * @return TraceTreeShortObject[]
     */
    public function findTree(TraceTreeFindParameters $parameters): array;
}
