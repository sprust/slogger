<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Entities\Trace\TraceCollectionNameObjects;
use App\Modules\Trace\Entities\Trace\TraceIndexInfoObject;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Parameters\TraceCreateParameters;
use App\Modules\Trace\Parameters\TraceUpdateParameters;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexFieldDto;
use App\Modules\Trace\Repositories\Dto\Trace\Profiling\TraceProfilingDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDto;
use Illuminate\Support\Carbon;

interface TraceRepositoryInterface
{
    public function createOne(TraceCreateParameters $trace): void;

    public function updateOne(TraceUpdateParameters $trace): bool;

    public function findOneDetailByTraceId(string $traceId): ?TraceDto;

    /**
     * @param int[]|null    $serviceIds
     * @param string[]|null $traceIds
     * @param string[]      $types
     * @param string[]      $tags
     * @param string[]      $statuses
     *
     * @return TraceDto[]
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
    ): array;

    /**
     * @param string[]|null $excludedTypes
     */
    public function findTraceIds(
        int $limit,
        ?Carbon $loggedAtTo = null,
        ?string $type = null,
        ?array $excludedTypes = null,
        ?bool $noCleared = null
    ): TraceCollectionNameObjects;

    /**
     * @param string[] $traceIds
     *
     * @return TraceDto[]
     */
    public function findByTraceIds(array $traceIds): array;

    public function findProfilingByTraceId(string $traceId): ?TraceProfilingDto;

    /**
     * @param string[]|null $traceIds
     * @param string[]|null $excludedTypes
     *
     * @return int - number of deleted records
     */
    public function deleteTraces(
        string $collectionName,
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
        string $collectionName,
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        ?string $type = null,
        ?array $excludedTypes = null
    ): int;

    /**
     * @param string[]                    $collectionNames
     * @param TraceDynamicIndexFieldDto[] $fields
     */
    public function createIndex(string $name, array $collectionNames, array $fields): bool;

    /**
     * @return TraceIndexInfoObject[]
     */
    public function getIndexProgressesInfo(): array;

    /**
     * @param string[] $collectionNames
     */
    public function deleteIndexByName(string $indexName, array $collectionNames): void;

    public function deleteEmptyCollections(Carbon $loggedAtTo): void;
}
