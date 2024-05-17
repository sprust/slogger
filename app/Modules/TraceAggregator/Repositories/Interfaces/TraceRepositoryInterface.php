<?php

namespace App\Modules\TraceAggregator\Repositories\Interfaces;

use App\Modules\TraceAggregator\Domain\Entities\Parameters\DataFilter\TraceDataFilterParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceSortParameters;
use App\Modules\TraceAggregator\Repositories\Dto\TraceDetailDto;
use App\Modules\TraceAggregator\Repositories\Dto\TraceDto;
use App\Modules\TraceAggregator\Repositories\Dto\TraceItemsPaginationDto;
use App\Modules\TraceAggregator\Repositories\Dto\TraceTypeDto;
use Illuminate\Support\Carbon;

interface TraceRepositoryInterface
{
    public function findOneByTraceId(string $traceId): ?TraceDetailDto;

    /**
     * @param int[]|null                 $serviceIds
     * @param string[]                   $types
     * @param string[]                   $tags
     * @param string[]                   $statuses
     * @param TraceSortParameters[]|null $sort
     */
    public function find(
        int $page = 1,
        int $perPage = 20,
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
        ?array $sort = null,
    ): TraceItemsPaginationDto;

    /**
     * @param string[] $traceIds
     *
     * @return TraceDto[]
     */
    public function findByTraceIds(array $traceIds): array;

    /**
     * @param string[] $traceIds
     *
     * @return TraceTypeDto[]
     */
    public function findTypeCounts(array $traceIds): array;

    public function findProfilingByTraceId(string $traceId): ?array;
}
