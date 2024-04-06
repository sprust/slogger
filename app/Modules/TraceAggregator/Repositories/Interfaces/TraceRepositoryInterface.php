<?php

namespace App\Modules\TraceAggregator\Repositories\Interfaces;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceItemObjects;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTreeShortObject;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceTreeFindParameters;
use App\Modules\TraceAggregator\Repositories\Dto\TraceDetailDto;

interface TraceRepositoryInterface
{
    public function findOneByTraceId(string $traceId): ?TraceDetailDto;

    public function find(TraceFindParameters $parameters): TraceItemObjects;

    /** @return TraceTreeShortObject[] */
    public function findTree(TraceTreeFindParameters $parameters): array;
}
