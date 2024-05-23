<?php

namespace App\Modules\TraceAggregator\Repositories\Interfaces;

use App\Modules\Common\Enums\TraceTimestampTypeEnum;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\DataFilter\TraceDataFilterParameters;
use App\Modules\TraceAggregator\Repositories\Dto\TraceTimestampsDto;
use Illuminate\Support\Carbon;

interface TraceTimestampsRepositoryInterface
{
    /**
     * @param int[]|null                 $serviceIds
     * @param string[]                   $types
     * @param string[]                   $tags
     * @param string[]                   $statuses
     *
     * @return TraceTimestampsDto[]
     */
    public function find(
        TraceTimestampTypeEnum $timestampType,
        ?array $serviceIds = null,
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        array $tags = [],
        array $statuses = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?TraceDataFilterParameters $data = null,
        ?bool $hasProfiling = null,
    ): array;
}
