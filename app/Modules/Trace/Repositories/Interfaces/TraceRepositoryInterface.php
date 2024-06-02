<?php

namespace App\Modules\Trace\Repositories\Interfaces;

use App\Modules\Trace\Domain\Entities\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Domain\Entities\Parameters\TraceSortParameters;
use App\Modules\Trace\Domain\Entities\Parameters\TraceUpdateParametersList;
use App\Modules\Trace\Repositories\Dto\TraceCreateDto;
use App\Modules\Trace\Repositories\Dto\TraceDetailDto;
use App\Modules\Trace\Repositories\Dto\TraceDto;
use App\Modules\Trace\Repositories\Dto\TraceItemsPaginationDto;
use App\Modules\Trace\Repositories\Dto\TraceLoggedAtDto;
use App\Modules\Trace\Repositories\Dto\TraceTimestampMetricDto;
use App\Modules\Trace\Repositories\Dto\TraceTreeDto;
use App\Modules\Trace\Repositories\Dto\TraceTypeDto;
use Illuminate\Support\Carbon;

interface TraceRepositoryInterface
{
    /**
     * @param TraceCreateDto[] $traces
     *
     * @return void
     */
    public function createMany(array $traces): void;

    public function updateMany(TraceUpdateParametersList $parametersList): int;

    /** @return TraceTreeDto[] */
    public function findTree(int $page = 1, int $perPage = 15, ?Carbon $to = null): array;

    /**
     * @return TraceLoggedAtDto[]
     */
    public function findLoggedAtList(int $page, int $perPage, Carbon $loggedAtTo): array;

    /**
     * @param TraceTimestampMetricDto[] $timestamps
     */
    public function updateTraceTimestamps(string $traceId, array $timestamps): void;

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

    /**
     * @param string[] $excludedTypes
     *
     * @return string[]
     */
    public function findTraceIds(
        int $limit,
        ?Carbon $loggedAtTo = null,
        ?string $type = null,
        ?array $excludedTypes = null
    ): array;

    /**
     * @param string[] $ids
     *
     * @return int - number of deleted records
     */
    public function deleteByTraceIds(array $ids): int;
}
