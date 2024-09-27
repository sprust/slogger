<?php

namespace App\Modules\Trace\Repositories\Interfaces;

use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterDto;
use App\Modules\Trace\Repositories\Dto\Profiling\TraceProfilingDto;
use App\Modules\Trace\Repositories\Dto\Timestamp\TraceTimestampMetricDto;
use App\Modules\Trace\Repositories\Dto\TraceCreateDto;
use App\Modules\Trace\Repositories\Dto\TraceDetailDto;
use App\Modules\Trace\Repositories\Dto\TraceDto;
use App\Modules\Trace\Repositories\Dto\TraceDynamicIndexFieldDto;
use App\Modules\Trace\Repositories\Dto\TraceItemsPaginationDto;
use App\Modules\Trace\Repositories\Dto\TraceLoggedAtDto;
use App\Modules\Trace\Repositories\Dto\TraceSortDto;
use App\Modules\Trace\Repositories\Dto\TraceTypeDto;
use App\Modules\Trace\Repositories\Dto\TraceUpdateDto;
use Illuminate\Support\Carbon;

interface TraceRepositoryInterface
{
    /**
     * @param TraceCreateDto[] $traces
     *
     * @return void
     */
    public function createMany(array $traces): void;

    /**
     * @param TraceUpdateDto[] $traces
     */
    public function updateMany(array $traces): int;

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
     * @param int[]|null          $serviceIds
     * @param string[]            $types
     * @param string[]            $tags
     * @param string[]            $statuses
     * @param TraceSortDto[]|null $sort
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
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterDto $data = null,
        ?bool $hasProfiling = null,
        ?array $sort = null,
    ): TraceItemsPaginationDto;

    /**
     * @return string[]
     */
    public function findTraceIds(
        int $limit = 20,
        ?Carbon $loggedAtTo = null,
        ?string $type = null,
        ?array $excludedTypes = null
    ): array;

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

    public function findProfilingByTraceId(string $traceId): ?TraceProfilingDto;

    /**
     * @param string[]|null $traceIds
     * @param string[]|null $excludedTypes
     *
     * @return int - number of deleted records
     */
    public function deleteTraces(
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        ?string $type = null,
        ?array $excludedTypes = null
    ): int;

    /**
     * @param string[]|null $traceIds
     * @param string[]|null $excludedTypes
     *
     * @return int - number of cleared records
     */
    public function clearTraces(
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        ?string $type = null,
        ?array $excludedTypes = null
    ): int;

    /**
     * @param TraceDynamicIndexFieldDto[] $fields
     */
    public function createIndex(string $name, array $fields): bool;

    public function findMinLoggedAt(): ?Carbon;

    public function deleteIndexByName(string $name): void;
}
