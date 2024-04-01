<?php

namespace App\Modules\TraceAggregator\Repositories;

use App\Modules\TraceAggregator\Dto\Objects\TraceDetailObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemObjects;
use App\Modules\TraceAggregator\Dto\Objects\TraceTreeShortObject;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceTreeFindParameters;
use App\Modules\TraceAggregator\Repositories\Dto\ServiceStatsPaginationDto;

interface TraceRepositoryInterface
{
    public function findOneByTraceId(string $traceId): ?TraceDetailObject;

    public function find(TraceFindParameters $parameters): TraceItemObjects;

    /** @return TraceTreeShortObject[] */
    public function findTree(TraceTreeFindParameters $parameters): array;

    public function findServiceStat(TraceFindParameters $parameters): ServiceStatsPaginationDto;
}
