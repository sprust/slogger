<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\Trace;
use App\Modules\Common\Repositories\PaginationInfoDto;
use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterDto;
use App\Modules\Trace\Repositories\Dto\Profiling\TraceProfilingDataDto;
use App\Modules\Trace\Repositories\Dto\Profiling\TraceProfilingDto;
use App\Modules\Trace\Repositories\Dto\Profiling\TraceProfilingItemDto;
use App\Modules\Trace\Repositories\Dto\Timestamp\TraceTimestampMetricDto;
use App\Modules\Trace\Repositories\Dto\TraceDetailDto;
use App\Modules\Trace\Repositories\Dto\TraceDto;
use App\Modules\Trace\Repositories\Dto\TraceIndexInfoDto;
use App\Modules\Trace\Repositories\Dto\TraceItemsPaginationDto;
use App\Modules\Trace\Repositories\Dto\TraceLoggedAtDto;
use App\Modules\Trace\Repositories\Dto\TraceServiceDto;
use App\Modules\Trace\Repositories\Dto\TraceSortDto;
use App\Modules\Trace\Repositories\Dto\TraceTypeDto;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\Trace\Repositories\Services\TraceQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\Exception;
use stdClass;
use Throwable;

readonly class TraceRepository implements TraceRepositoryInterface
{
    public function __construct(
        private TraceQueryBuilder $traceQueryBuilder
    ) {
    }

    public function createMany(array $traces): void
    {
        $timestamp = new UTCDateTime(now());

        $operations = [];

        foreach ($traces as $trace) {
            $operations[] = [
                'updateOne' => [
                    [
                        'sid' => $trace->serviceId,
                        'tid' => $trace->traceId,
                    ],
                    [
                        '$set'         => [
                            'ptid' => $trace->parentTraceId,
                            'tp'   => $trace->type,
                            'st'   => $trace->status,
                            'tgs'  => $this->prepareTagsForSave($trace->tags),
                            'dt'   => $this->prepareData(
                                json_decode($trace->data, true)
                            ),
                            'dur'  => $trace->duration,
                            'mem'  => $trace->memory,
                            'cpu'  => $trace->cpu,
                            'tss'  => $this->makeTimestampsData($trace->timestamps),
                            'lat'  => new UTCDateTime($trace->loggedAt),
                            'uat'  => $timestamp,
                        ],
                        '$setOnInsert' => [
                            'cat' => $timestamp,
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

    public function updateMany(array $traces): int
    {
        $timestamp = new UTCDateTime(now());

        $operations = [];

        foreach ($traces as $trace) {
            $hasProfiling = is_null($trace->profiling) ? null : !empty($trace->profiling->items);

            $operations[] = [
                'updateOne' => [
                    [
                        'sid' => $trace->serviceId,
                        'tid' => $trace->traceId,
                    ],
                    [
                        '$set' => [
                            'st'  => $trace->status,
                            ...(is_null($hasProfiling)
                                ? []
                                : [
                                    'hpr' => $hasProfiling,
                                ]),
                            ...(is_null($trace->profiling)
                                ? []
                                : [
                                    'pr' => [
                                        'mainCaller' => $trace->profiling->mainCaller,
                                        'items'      => $trace->profiling->items,
                                    ],
                                ]),
                            ...(is_null($trace->tags)
                                ? []
                                : [
                                    'tgs' => $this->prepareTagsForSave($trace->tags),
                                ]),
                            ...(is_null($trace->data)
                                ? []
                                : [
                                    'dt' => $this->prepareData(
                                        json_decode($trace->data, true)
                                    ),
                                ]),
                            ...(is_null($trace->duration)
                                ? []
                                : [
                                    'dur' => $trace->duration,
                                ]),
                            ...(is_null($trace->memory)
                                ? []
                                : [
                                    'mem' => $trace->memory,
                                ]),
                            ...(is_null($trace->cpu)
                                ? []
                                : [
                                    'cpu' => $trace->cpu,
                                ]),
                            'uat' => $timestamp,
                        ],
                    ],
                ],
            ];
        }

        return Trace::collection()->bulkWrite($operations)->getModifiedCount();
    }

    public function findLoggedAtList(int $page, int $perPage, Carbon $loggedAtTo): array
    {
        return Trace::query()
            ->select([
                'tid',
                'lat',
            ])
            ->where('lat', '<=', $loggedAtTo)
            ->orderBy('_id')
            ->forPage(page: $page, perPage: $perPage)
            ->get()
            ->map(
                fn(Trace $trace) => new TraceLoggedAtDto(
                    traceId: $trace->tid,
                    loggedAt: $trace->lat
                )
            )
            ->toArray();
    }

    public function updateTraceTimestamps(string $traceId, array $timestamps): void
    {
        Trace::query()
            ->where('tid', $traceId)
            ->update([
                'tss' => $this->makeTimestampsData($timestamps),
            ]);
    }

    public function findOneByTraceId(string $traceId): ?TraceDetailDto
    {
        /** @var Trace|null $trace */
        $trace = Trace::query()
            ->select([
                '_id',
                'sid',
                'tid',
                'ptid',
                'tp',
                'st',
                'tgs',
                'dt',
                'dur',
                'mem',
                'cpu',
                'hpr',
                'lat',
                'cat',
                'uat',
            ])
            ->where('tid', $traceId)
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
            traceId: $trace->tid,
            parentTraceId: $trace->ptid,
            type: $trace->tp,
            status: $trace->st,
            tags: $this->parseTagsFromDb($trace->tgs),
            data: $trace->dt,
            duration: $trace->dur,
            memory: $trace->mem,
            cpu: $trace->cpu,
            hasProfiling: $trace->hpr ?? false,
            loggedAt: $trace->lat,
            createdAt: $trace->cat,
            updatedAt: $trace->uat
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
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterDto $data = null,
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
            memoryFrom: $memoryFrom,
            memoryTo: $memoryTo,
            cpuFrom: $cpuFrom,
            cpuTo: $cpuTo,
            data: $data,
            hasProfiling: $hasProfiling,
        );

        $tracesPaginator = $builder
            ->select([
                '_id',
                'sid',
                'tid',
                'ptid',
                'tp',
                'st',
                'tgs',
                'dt',
                'dur',
                'mem',
                'cpu',
                'hpr',
                'lat',
                'cat',
                'uat',
            ])
            ->with([
                'service',
            ])
            ->when(
                !is_null($sort),
                function (Builder $query) use ($sort) {
                    /** @var TraceSortDto[] $sort */

                    foreach ($sort as $sortItem) {
                        $query->orderBy($sortItem->field, $sortItem->directionEnum->value);
                    }
                }
            )
            ->forPage(
                page: $page,
                perPage: $perPage
            );

        /** @var Trace[] $traces */
        $traces = $tracesPaginator->get()->all();

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
                    traceId: $trace->tid,
                    parentTraceId: $trace->ptid,
                    type: $trace->tp,
                    status: $trace->st,
                    tags: $this->parseTagsFromDb($trace->tgs),
                    data: $trace->dt,
                    duration: $trace->dur,
                    memory: $trace->mem,
                    cpu: $trace->cpu,
                    hasProfiling: $trace->hpr ?? false,
                    loggedAt: $trace->lat,
                    createdAt: $trace->cat,
                    updatedAt: $trace->uat
                ),
                $traces
            ),
            paginationInfo: new PaginationInfoDto(
                total: 0,
                perPage: $perPage,
                currentPage: $page,
            )
        );
    }

    public function findTraceIds(
        int $limit = 20,
        ?Carbon $loggedAtTo = null,
        ?string $type = null,
        ?array $excludedTypes = null
    ): array {
        $builder = Trace::query()
            ->when(
                !is_null($loggedAtTo),
                fn(Builder $query) => $query->where('lat', '<=', new UTCDateTime($loggedAtTo))
            )
            ->when(
                !is_null($type),
                fn(Builder $query) => $query->where('tp', $type)
            )
            ->when(
                is_null($type) && !is_null($excludedTypes),
                fn(Builder $query) => $query->whereNotIn('tp', $excludedTypes)
            )
            ->select(['tid'])
            ->toBase()
            ->limit($limit);

        return $builder->get()
            ->map(function (array $document) {
                return (string) $document['tid'];
            })
            ->all();
    }

    public function findByTraceIds(array $traceIds): array
    {
        /** @var TraceDto[] $children */
        $children = [];

        foreach (collect($traceIds)->chunk(1000) as $childrenIdsChunk) {
            Trace::query()
                ->select([
                    '_id',
                    'sid',
                    'tid',
                    'ptid',
                    'tp',
                    'st',
                    'tgs',
                    'dur',
                    'mem',
                    'cpu',
                    'lat',
                    'cat',
                    'uat',
                ])
                ->with([
                    'service' => fn(BelongsTo $relation) => $relation->select([
                        'id',
                        'name',
                    ]),
                ])
                ->whereIn('tid', $childrenIdsChunk)
                ->each(function (Trace $trace) use (&$children) {
                    $children[] = new TraceDto(
                        id: $trace->_id,
                        service: $trace->service
                            ? new TraceServiceDto(
                                id: $trace->service->id,
                                name: $trace->service->name,
                            )
                            : null,
                        traceId: $trace->tid,
                        parentTraceId: $trace->ptid,
                        type: $trace->tp,
                        status: $trace->st,
                        tags: $this->parseTagsFromDb($trace->tgs),
                        duration: $trace->dur,
                        memory: $trace->mem,
                        cpu: $trace->cpu,
                        loggedAt: $trace->lat,
                        createdAt: $trace->cat,
                        updatedAt: $trace->uat
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
                'ptid' => [
                    '$in' => $traceIds,
                ],
            ],
        ];

        $pipeline[] = [
            '$group' => [
                '_id'   => [
                    'parentTraceId' => '$ptid',
                    'type'          => '$tp',
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

    public function findProfilingByTraceId(string $traceId): ?TraceProfilingDto
    {
        /** @var Trace|null $trace */
        $trace = Trace::query()->where('tid', $traceId)->first();

        $profilingData = $trace?->pr;

        if (is_null($profilingData)) {
            return null;
        }

        return new TraceProfilingDto(
            mainCaller: $profilingData['mainCaller'],
            items: array_map(
                fn(array $itemData) => new TraceProfilingItemDto(
                    raw: $itemData['raw'],
                    calling: $itemData['calling'],
                    callable: $itemData['callable'],
                    data: array_map(
                        fn(array $itemDataItem) => new TraceProfilingDataDto(
                            name: $itemDataItem['name'],
                            value: $itemDataItem['value']
                        ),
                        $itemData['data']
                    ),
                ),
                $profilingData['items']
            )
        );
    }

    public function deleteTraces(
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        ?string $type = null,
        ?array $excludedTypes = null
    ): int {
        return Trace::query()
            ->when(
                !is_null($traceIds),
                fn(Builder $query) => $query->whereIn('tid', $traceIds)
            )
            ->when(
                !is_null($loggedAtFrom),
                fn(Builder $query) => $query->where('lat', '>=', new UTCDateTime($loggedAtFrom))
            )
            ->when(
                !is_null($loggedAtTo),
                fn(Builder $query) => $query->where('lat', '<=', new UTCDateTime($loggedAtTo))
            )
            ->when(
                !is_null($type),
                fn(Builder $query) => $query->where('tp', $type)
            )
            ->when(
                is_null($type) && !is_null($excludedTypes),
                fn(Builder $query) => $query->whereNotIn('tp', $excludedTypes)
            )
            ->delete();
    }

    public function clearTraces(
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        ?string $type = null,
        ?array $excludedTypes = null
    ): int {
        return Trace::query()
            ->where(function (Builder $query) {
                $query->where('cl', false)
                    ->orWhere('cl', 'exists', false);
            })
            ->when(
                !is_null($traceIds),
                fn(Builder $query) => $query->whereIn('tid', $traceIds)
            )
            ->when(
                !is_null($loggedAtFrom),
                fn(Builder $query) => $query->where('lat', '>=', new UTCDateTime($loggedAtFrom))
            )
            ->when(
                !is_null($loggedAtTo),
                fn(Builder $query) => $query->where('lat', '<=', new UTCDateTime($loggedAtTo))
            )
            ->when(!is_null($type), fn(Builder $query) => $query->where('tp', $type))
            ->when(
                is_null($type) && !is_null($excludedTypes),
                fn(Builder $query) => $query->whereNotIn('tp', $excludedTypes)
            )
            ->update([
                'dt'  => new stdClass(),
                'pr'  => null,
                'hpr' => false,
                'cl'  => true,
            ]);
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

    /**
     * @throws Throwable
     */
    public function createIndex(string $name, array $fields): bool
    {
        if (empty($fields)) {
            return false;
        }

        $index = [];

        foreach ($fields as $field) {
            $index[$field->fieldName] = 1;
        }

        try {
            Trace::collection()->createIndex(
                $index,
                [
                    'name'       => $name,
                    'background' => true,
                ],
            );
        } catch (Throwable $exception) {
            if (str_starts_with($exception->getMessage(), 'Index already exists with a different name')) {
                return false;
            }

            throw $exception;
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function getIndexProgressInfo(string $name): ?TraceIndexInfoDto
    {
        $operations = Trace::collection()->getManager()
            ->executeCommand(
                'admin',
                new Command(
                    [
                        'currentOp' => true,
                        '$and'      => [
                            ['op' => 'command'],
                            [
                                'command.createIndexes' => [
                                    '$exists' => true,
                                ],
                            ],
                            [
                                'progress' => [
                                    '$exists' => true,
                                ],
                            ],
                            ['command.indexes.name' => $name],
                        ],
                    ]
                )
            );

        $operation = iterator_to_array($operations)[0]->inprog[0] ?? null;

        if (!$operation) {
            return null;
        }

        return new TraceIndexInfoDto(
            $name,
            round($operation->progress->done / $operation->progress->total * 100, 2)
        );
    }

    public function findMinLoggedAt(): ?Carbon
    {
        /** @var UTCDateTime|null $min */
        $min = Trace::query()->min('lat');

        return $min ? new Carbon($min->toDateTime()) : null;
    }

    public function deleteIndexByName(string $name): void
    {
        Trace::collection()->dropIndex($name);
    }

    /**
     * @param string[] $tags
     */
    private function prepareTagsForSave(array $tags): array
    {
        return array_map(
            fn(string $tag) => [
                'nm' => $tag,
            ],
            $tags
        );
    }

    /**
     * @return string[]
     */
    private function parseTagsFromDb(array $tags): array
    {
        return array_map(
            fn(string|array $tag) => is_array($tag) ? $tag['nm'] : $tag,
            $tags
        );
    }

    private function prepareData(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $this->prepareDataRecursive($result, $key, $value);
        }

        return $result;
    }

    private function prepareDataRecursive(array &$result, mixed $key, mixed $value): void
    {
        if (!is_array($value)) {
            $result[$key] = $value;

            return;
        }

        if (!$value) {
            $result[$key] = new stdClass();

            return;
        }

        $result[$key] = [];

        $isList = Arr::isList($value);

        foreach ($value as $valueItemKey => $valueItem) {
            $this->prepareDataRecursive(
                result: $result[$key],
                key: $isList ? "_$valueItemKey" : $valueItemKey,
                value: $valueItem
            );
        }
    }
}
