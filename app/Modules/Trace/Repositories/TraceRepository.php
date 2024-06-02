<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\Trace;
use App\Modules\Common\Repositories\PaginationInfoDto;
use App\Modules\Trace\Domain\Entities\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Domain\Entities\Parameters\TraceCreateParametersList;
use App\Modules\Trace\Domain\Entities\Parameters\TraceUpdateParametersList;
use App\Modules\Trace\Repositories\Dto\TraceDetailDto;
use App\Modules\Trace\Repositories\Dto\TraceDto;
use App\Modules\Trace\Repositories\Dto\TraceItemsPaginationDto;
use App\Modules\Trace\Repositories\Dto\TraceLoggedAtDto;
use App\Modules\Trace\Repositories\Dto\TraceServiceDto;
use App\Modules\Trace\Repositories\Dto\TraceTimestampMetricDto;
use App\Modules\Trace\Repositories\Dto\TraceTreeDto;
use App\Modules\Trace\Repositories\Dto\TraceTypeDto;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\Trace\Repositories\Services\TraceQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;

readonly class TraceRepository implements TraceRepositoryInterface
{
    public function __construct(
        private TraceQueryBuilder $traceQueryBuilder
    ) {
    }

    public function createMany(TraceCreateParametersList $parametersList): void
    {
        $timestamp = new UTCDateTime(now());

        $operations = [];

        foreach ($parametersList->getItems() as $parameters) {
            $operations[] = [
                'updateOne' => [
                    [
                        'serviceId' => $parameters->serviceId,
                        'traceId'   => $parameters->traceId,
                    ],
                    [
                        '$set'         => [
                            'parentTraceId' => $parameters->parentTraceId,
                            'type'          => $parameters->type,
                            'status'        => $parameters->status,
                            'tags'          => $parameters->tags,
                            'data'          => json_decode($parameters->data, true),
                            'duration'      => $parameters->duration,
                            'memory'        => $parameters->memory,
                            'cpu'           => $parameters->cpu,
                            'timestamps'    => $this->makeTimestampsData($parameters->timestamps),
                            'loggedAt'      => new UTCDateTime($parameters->loggedAt),
                            'updatedAt'     => $timestamp,
                        ],
                        '$setOnInsert' => [
                            'createdAt' => $timestamp,
                        ],
                    ],
                    [
                        'upsert' => true,
                    ],
                ],
            ];
        }

        Trace::collection()->bulkWrite($operations);
    }

    public function updateMany(TraceUpdateParametersList $parametersList): int
    {
        $timestamp = new UTCDateTime(now());

        $operations = [];

        foreach ($parametersList->getItems() as $parameters) {
            $hasProfiling = is_null($parameters->profiling) ? null : !empty($parameters->profiling->getItems());

            $operations[] = [
                'updateOne' => [
                    [
                        'serviceId' => $parameters->serviceId,
                        'traceId'   => $parameters->traceId,
                    ],
                    [
                        '$set' => [
                            'status'    => $parameters->status,
                            ...(is_null($hasProfiling)
                                ? []
                                : [
                                    'hasProfiling' => $hasProfiling,
                                ]),
                            ...(is_null($parameters->profiling)
                                ? []
                                : [
                                    'profiling' => [
                                        'mainCaller' => $parameters->profiling->getMainCaller(),
                                        'items'      => $parameters->profiling->getItems(),
                                    ],
                                ]),
                            ...(is_null($parameters->tags)
                                ? []
                                : [
                                    'tags' => $parameters->tags,
                                ]),
                            ...(is_null($parameters->data)
                                ? []
                                : [
                                    'data' => json_decode($parameters->data, true),
                                ]),
                            ...(is_null($parameters->duration)
                                ? []
                                : [
                                    'duration' => $parameters->duration,
                                ]),
                            ...(is_null($parameters->memory)
                                ? []
                                : [
                                    'memory' => $parameters->memory,
                                ]),
                            ...(is_null($parameters->cpu)
                                ? []
                                : [
                                    'cpu' => $parameters->cpu,
                                ]),
                            'updatedAt' => $timestamp,
                        ],
                    ],
                ],
            ];
        }

        return Trace::collection()->bulkWrite($operations)->getModifiedCount();
    }

    public function findTree(int $page = 1, int $perPage = 15, ?Carbon $to = null): array
    {
        return Trace::query()
            ->select([
                'traceId',
                'parentTraceId',
                'loggedAt',
            ])
            ->when(
                $to,
                fn(Builder $query) => $query->where('loggedAt', '<=', new UTCDateTime($to))
            )
            ->forPage(
                page: $page,
                perPage: $perPage
            )
            ->get()
            ->map(
                fn(Trace $trace) => new TraceTreeDto(
                    traceId: $trace->traceId,
                    parentTraceId: $trace->parentTraceId,
                    loggedAt: $trace->loggedAt
                )
            )
            ->toArray();
    }

    public function findLoggedAtList(int $page, int $perPage, Carbon $loggedAtTo): array
    {
        return Trace::query()
            ->select([
                'traceId',
                'loggedAt',
            ])
            ->where('loggedAt', '<=', $loggedAtTo)
            ->orderBy('_id')
            ->forPage(page: $page, perPage: $perPage)
            ->get()
            ->map(
                fn(Trace $trace) => new TraceLoggedAtDto(
                    traceId: $trace->traceId,
                    loggedAt: $trace->loggedAt
                )
            )
            ->toArray();
    }

    public function updateTraceTimestamps(string $traceId, array $timestamps): void
    {
        Trace::query()
            ->where('traceId', $traceId)
            ->update([
                'timestamps' => $this->makeTimestampsData($timestamps),
            ]);
    }

    public function findOneByTraceId(string $traceId): ?TraceDetailDto
    {
        /** @var Trace|null $trace */
        $trace = Trace::query()
            ->select([
                '_id',
                'serviceId',
                'traceId',
                'parentTraceId',
                'type',
                'status',
                'tags',
                'data',
                'duration',
                'memory',
                'cpu',
                'hasProfiling',
                'loggedAt',
                'createdAt',
                'updatedAt',
            ])
            ->where('traceId', $traceId)
            ->first();

        if (!$trace) {
            return null;
        }

        return new TraceDetailDto(
            id: $trace->_id,
            service: $trace->service
                ? new TraceServiceDto(
                    id: $trace->service->id,
                    name: $trace->service->name,
                )
                : null,
            traceId: $trace->traceId,
            parentTraceId: $trace->parentTraceId,
            type: $trace->type,
            status: $trace->status,
            tags: $trace->tags,
            data: $trace->data,
            duration: $trace->duration,
            memory: $trace->memory,
            cpu: $trace->cpu,
            hasProfiling: $trace->hasProfiling ?? false,
            loggedAt: $trace->loggedAt,
            createdAt: $trace->createdAt,
            updatedAt: $trace->updatedAt
        );
    }

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
    ): TraceItemsPaginationDto {
        $builder = $this->traceQueryBuilder->make(
            serviceIds: $serviceIds,
            traceIds: $traceIds,
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            types: $types,
            tags: $tags,
            statuses: $statuses,
            durationFrom: $durationFrom,
            durationTo: $durationTo,
            data: $data,
            hasProfiling: $hasProfiling,
        );

        $tracesPaginator = $builder
            ->select([
                '_id',
                'serviceId',
                'traceId',
                'parentTraceId',
                'type',
                'status',
                'tags',
                'data',
                'duration',
                'memory',
                'cpu',
                'hasProfiling',
                'loggedAt',
                'createdAt',
                'updatedAt',
            ])
            ->with([
                'service',
            ])
            ->when(
                !is_null($sort),
                function (Builder $query) use ($sort) {
                    foreach ($sort as $sortItem) {
                        $query->orderBy($sortItem->field, $sortItem->directionEnum->value);
                    }
                }
            )
            ->paginate(
                perPage: $perPage,
                page: $page
            );

        /** @var Trace[] $traces */
        $traces = $tracesPaginator->items();

        return new TraceItemsPaginationDto(
            items: array_map(
                fn(Trace $trace) => new TraceDetailDto(
                    id: $trace->_id,
                    service: $trace->service
                        ? new TraceServiceDto(
                            id: $trace->service->id,
                            name: $trace->service->name,
                        )
                        : null,
                    traceId: $trace->traceId,
                    parentTraceId: $trace->parentTraceId,
                    type: $trace->type,
                    status: $trace->status,
                    tags: $trace->tags,
                    data: $trace->data,
                    duration: $trace->duration,
                    memory: $trace->memory,
                    cpu: $trace->cpu,
                    hasProfiling: $trace->hasProfiling ?? false,
                    loggedAt: $trace->loggedAt,
                    createdAt: $trace->createdAt,
                    updatedAt: $trace->updatedAt
                ),
                $traces
            ),
            paginationInfo: new PaginationInfoDto(
                total: $tracesPaginator->total(),
                perPage: $perPage,
                currentPage: $page,
            )
        );
    }

    public function findByTraceIds(array $traceIds): array
    {
        /** @var TraceDto[] $children */
        $children = [];

        foreach (collect($traceIds)->chunk(1000) as $childrenIdsChunk) {
            Trace::query()
                ->select([
                    '_id',
                    'serviceId',
                    'traceId',
                    'parentTraceId',
                    'type',
                    'status',
                    'tags',
                    'duration',
                    'memory',
                    'cpu',
                    'loggedAt',
                    'createdAt',
                    'updatedAt',
                ])
                ->with([
                    'service' => fn(BelongsTo $relation) => $relation->select([
                        'id',
                        'name',
                    ]),
                ])
                ->whereIn('traceId', $childrenIdsChunk)
                ->each(function (Trace $trace) use (&$children) {
                    $children[] = new TraceDto(
                        id: $trace->_id,
                        service: $trace->service
                            ? new TraceServiceDto(
                                id: $trace->service->id,
                                name: $trace->service->name,
                            )
                            : null,
                        traceId: $trace->traceId,
                        parentTraceId: $trace->parentTraceId,
                        type: $trace->type,
                        status: $trace->status,
                        tags: $trace->tags,
                        duration: $trace->duration,
                        memory: $trace->memory,
                        cpu: $trace->cpu,
                        loggedAt: $trace->loggedAt,
                        createdAt: $trace->createdAt,
                        updatedAt: $trace->updatedAt
                    );
                });
        }

        return $children;
    }

    public function findTypeCounts(array $traceIds): array
    {
        $pipeline = [];

        $pipeline[] = [
            '$match' => [
                'parentTraceId' => [
                    '$in' => $traceIds,
                ],
            ],
        ];

        $pipeline[] = [
            '$group' => [
                '_id'   => [
                    'parentTraceId' => '$parentTraceId',
                    'type'          => '$type',
                ],
                'count' => [
                    '$sum' => 1,
                ],
            ],
        ];

        $pipeline[] = [
            '$project' => [
                '_id'   => 1,
                'count' => 1,
            ],
        ];

        $pipeline[] = [
            '$sort' => [
                'count'    => -1,
                '_id.type' => 1,
            ],
        ];

        $typesAggregation = Trace::collection()->aggregate($pipeline);

        $types = [];

        foreach ($typesAggregation as $item) {
            $types[] = new TraceTypeDto(
                traceId: $item->_id->parentTraceId,
                type: $item->_id->type,
                count: $item->count,
            );
        }

        return $types;
    }

    public function findProfilingByTraceId(string $traceId): ?array
    {
        /** @var Trace|null $trace */
        $trace = Trace::query()->where('traceId', $traceId)->first();

        return $trace?->profiling;
    }

    public function findTraceIds(
        int $limit,
        ?Carbon $loggedAtTo = null,
        ?string $type = null,
        ?array $excludedTypes = null
    ): array {
        return Trace::query()
            ->when(
                !is_null($loggedAtTo),
                fn(Builder $query) => $query->where('loggedAt', '<=', new UTCDateTime($loggedAtTo))
            )
            ->when(!is_null($type), fn(Builder $query) => $query->where('type', $type))
            ->when(
                is_null($type) && !is_null($excludedTypes),
                fn(Builder $query) => $query->whereNotIn('type', $excludedTypes)
            )
            ->take($limit)
            ->pluck('traceId')
            ->toArray();
    }

    public function deleteByTraceIds(array $ids): int
    {
        return Trace::query()->whereIn('traceId', $ids)->delete();
    }

    /**
     * @param TraceTimestampMetricDto[] $timestamps
     */
    private function makeTimestampsData(array $timestamps): array
    {
        $result = [];

        foreach ($timestamps as $timestamp) {
            $result[$timestamp->key] = new UTCDateTime($timestamp->value);
        }

        return $result;
    }
}
