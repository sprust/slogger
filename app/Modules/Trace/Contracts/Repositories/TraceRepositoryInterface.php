<?php

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampMetricObject;
use App\Modules\Trace\Entities\Trace\TraceDetailObject;
use App\Modules\Trace\Entities\Trace\TraceDetailPaginationObject;
use App\Modules\Trace\Entities\Trace\TraceIndexInfoObject;
use App\Modules\Trace\Entities\Trace\TraceObject;
use App\Modules\Trace\Entities\Trace\TraceTypeCountedObject;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Parameters\TraceCreateParameters;
use App\Modules\Trace\Parameters\TraceSortParameters;
use App\Modules\Trace\Parameters\TraceUpdateParameters;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexFieldDto;
use App\Modules\Trace\Repositories\Dto\Trace\Profiling\TraceProfilingDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceLoggedAtDto;
use Illuminate\Support\Carbon;

interface TraceRepositoryInterface
{
    /**
     * @param TraceCreateParameters[] $traces
     *
     * @return void
     */
    public function createMany(array $traces): void;

    /**
     * @param TraceUpdateParameters[] $traces
     */
    public function updateMany(array $traces): int;

    /**
     * @return TraceLoggedAtDto[]
     */
    public function findLoggedAtList(int $page, int $perPage, Carbon $loggedAtTo): array;

    /**
     * @param TraceTimestampMetricObject[] $timestamps
     */
    public function updateTraceTimestamps(string $traceId, array $timestamps): void;

    public function findOneDetailByTraceId(string $traceId): ?TraceDetailObject;

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
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterParameters $data = null,
        ?bool $hasProfiling = null,
        ?array $sort = null,
    ): TraceDetailPaginationObject;

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
     * @return TraceObject[]
     */
    public function findByTraceIds(array $traceIds): array;

    /**
     * @param string[] $traceIds
     *
     * @return TraceTypeCountedObject[]
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

    /**
     * @return TraceIndexInfoObject[]
     */
    public function getIndexProgressesInfo(): array;

    public function findMinLoggedAt(): ?Carbon;

    public function deleteIndexByName(string $name): void;
}
