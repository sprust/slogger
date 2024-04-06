<?php

namespace App\Modules\TraceAggregator\Repositories;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceDetailObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceItemObjects;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTreeShortObject;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceTreeFindParameters;

interface TraceRepositoryInterface
{
    public function findOneByTraceId(string $traceId): ?TraceDetailObject;

    public function find(TraceFindParameters $parameters): TraceItemObjects;

    /** @return TraceTreeShortObject[] */
    public function findTree(TraceTreeFindParameters $parameters): array;
}
