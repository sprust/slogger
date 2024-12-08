<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\Trace;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampMetricObject;
use App\Modules\Trace\Entities\Trace\TraceDetailObject;
use App\Modules\Trace\Entities\Trace\TraceIndexInfoObject;
use App\Modules\Trace\Entities\Trace\TraceObject;
use App\Modules\Trace\Entities\Trace\TraceServiceObject;
use App\Modules\Trace\Entities\Trace\TraceTypeCountedObject;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Repositories\Dto\Trace\Profiling\TraceProfilingDataDto;
use App\Modules\Trace\Repositories\Dto\Trace\Profiling\TraceProfilingDto;
use App\Modules\Trace\Repositories\Dto\Trace\Profiling\TraceProfilingItemDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDto;
use App\Modules\Trace\Repositories\Services\PeriodicTraceService;
use App\Modules\Trace\Repositories\Services\TraceDataToObjectBuilder;
use App\Modules\Trace\Repositories\Services\TraceQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\Exception;
use stdClass;
use Throwable;

readonly class TraceRepository implements TraceRepositoryInterface
{
    public function __construct(
        private TraceQueryBuilder $traceQueryBuilder,
        private PeriodicTraceService $periodicTraceService
    ) {
    }

    public function createMany(array $traces): void
    {
        $timestamp = new UTCDateTime(now());

        /**
         * @var array<string, array{collection: Collection, operations: array<string, mixed>}> $operationsByCollections
         */
        $operationsByCollections = [];

        foreach ($traces as $trace) {
            $collectionName = $this->periodicTraceService->makeCollectionName($trace->loggedAt);

            $operationsByCollections[$collectionName] ??= [
                'collection' => $this->periodicTraceService->initCollection($trace->loggedAt),
                'operations' => [],
            ];

            $operationsByCollections[$collectionName]['operations'][] = [
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

        foreach ($operationsByCollections as $collection) {
            $collection['collection']->bulkWrite($collection['operations']);
        }
    }

    public function updateMany(array $traces): int
    {
        // TODO: Does not have the loggedAt. To think about an another resolving.

        $periodicTraceService = $this->periodicTraceService;

        $collectionNames = array_reverse($periodicTraceService->detectCollectionNames());

        $timestamp = new UTCDateTime(now());

        /**
         * @var array<string, array{collection: Collection, operations: array<string, mixed>}> $operationsByCollections
         */
        $operationsByCollections = [];

        foreach ($traces as $trace) {
            /** @var string|null $collectionName */
            $collectionName = Arr::first(
                $collectionNames,
                static function (string $collectionName) use ($trace, $periodicTraceService) {
                    return $periodicTraceService->isTraceExists($collectionName, $trace->traceId);
                }
            );

            if (!$collectionName) {
                continue;
            }

            $profilingItems = $trace->profiling?->getItems();

            $hasProfiling = !is_null($profilingItems) && (count($profilingItems) > 0);

            $operationsByCollections[$collectionName] ??= [
                'collection' => $this->periodicTraceService->selectCollectionByName($collectionName),
                'operations' => [],
            ];

            $operationsByCollections[$collectionName]['operations'][] = [
                'updateOne' => [
                    [
                        'sid' => $trace->serviceId,
                        'tid' => $trace->traceId,
                    ],
                    [
                        '$set' => [
                            'st'  => $trace->status,
                            ...(!$hasProfiling
                                ? []
                                : [
                                    'hpr' => true,
                                    'pr'  => [
                                        'mainCaller' => $trace->profiling->getMainCaller(),
                                        'items'      => $profilingItems,
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

        $modifiedCount = 0;

        foreach ($operationsByCollections as $collection) {
            $modifiedCount += $collection['collection']
                ->bulkWrite($collection['operations'])
                ->getModifiedCount();
        }

        return $modifiedCount;
    }

    public function findOneDetailByTraceId(string $traceId): ?TraceDetailObject
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

        return $this->makeDetailFromModel($trace);
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
        ?TraceDataFilterParameters $data = null,
        ?bool $hasProfiling = null,
        // TODO: delete this parameter
        ?array $sort = null,
    ): array {
        $collectionNames = $this->periodicTraceService->detectCollectionNames(
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo
        );

        if (!count($collectionNames)) {
            return [];
        }

        $collectionNames = array_values(
            array_reverse($collectionNames)
        );

        // TODO: refactored for unusage of model
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

        $match = $this->traceQueryBuilder->makeMqlMatchFromBuilder($builder);

        $pipeline = [
            ...(count($match) ? [['$match' => $match]] : []),
            [
                '$sort' => [
                    'lat' => -1,
                    '_id' => 1,
                ],
            ],
        ];

        /** @var TraceDto[] $traces */
        $traces = [];

        $totalFoundCount   = 0;
        $resultTracesCount = 0;

        $offsetCount = ($page - 1) * $perPage;

        $collected = false;

        foreach ($collectionNames as $collectionName) {
            if ($collected) {
                break;
            }

            $collectionPage = 0;

            while (true) {
                if ($collected) {
                    break;
                }

                ++$collectionPage;

                $collectionPipeline = [
                    ...$pipeline,
                    [
                        '$skip' => ($collectionPage - 1) * $perPage,
                    ],
                    [
                        '$limit' => $perPage,
                    ],
                ];

                $cursor = $this->periodicTraceService->aggregate(
                    collectionName: $collectionName,
                    pipeline: $collectionPipeline
                );

                $documents = iterator_to_array($cursor);

                $documentsCount = count($documents);

                if (!$documentsCount) {
                    break;
                }

                $totalFoundCount += $documentsCount;

                if ($offsetCount >= $totalFoundCount) {
                    continue;
                }

                foreach ($documents as $document) {
                    $traces[] = $this->makeTraceDtoFromDocument($document);

                    if (++$resultTracesCount >= $perPage) {
                        $collected = true;

                        break;
                    }
                }
            }
        }

        return $traces;
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
        /** @var TraceObject[] $children */
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
                    $children[] = new TraceObject(
                        id: $trace->_id,
                        service: $trace->service
                            ? new TraceServiceObject(
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
            $types[] = new TraceTypeCountedObject(
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
     * @param TraceTimestampMetricObject[] $timestamps
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
    public function createIndex(string $name, array $collectionNames, array $fields): bool
    {
        if (empty($collectionNames) || empty($fields)) {
            return false;
        }

        $index = [];

        foreach ($fields as $field) {
            $index[$field->fieldName] = 1;
        }

        foreach ($collectionNames as $collectionName) {
            try {
                $this->periodicTraceService->createIndex(
                    indexName: $name,
                    collectionName: $collectionName,
                    index: $index
                );
            } catch (Throwable $exception) {
                if (str_starts_with($exception->getMessage(), 'Index already exists with a different name')) {
                    continue;
                }

                throw $exception;
            }
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function getIndexProgressesInfo(): array
    {
        $operations = Trace::collection()->getManager()
            ->executeCommand(
                'admin',
                new Command(
                    [
                        'currentOp' => true,
                        '$and'      => [
                            [
                                'op' => 'command',
                            ],
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
                        ],
                    ]
                )
            );

        /** @var object[] $operations */
        $operations = iterator_to_array($operations)[0]->inprog ?? [];

        $infoList = [];

        foreach ($operations as $operation) {
            $progressTotal = $operation->progress->total;
            $progressDone  = $operation->progress->done;

            $infoList[] = new TraceIndexInfoObject(
                name: $operation->command->indexes[0]?->name ?? 'untitled',
                progress: $progressTotal ? round($progressDone / $progressTotal * 100, 2) : 0
            );
        }

        return $infoList;
    }

    public function findMinLoggedAt(): ?Carbon
    {
        /** @var UTCDateTime|null $min */
        $min = Trace::query()->min('lat');

        return $min ? new Carbon($min->toDateTime()) : null;
    }

    public function deleteIndexByName(string $indexName, array $collectionNames): void
    {
        foreach ($collectionNames as $collectionName) {
            $this->periodicTraceService->deleteIndex(
                collectionName: $collectionName,
                indexName: $indexName
            );
        }
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

    private function makeDetailFromModel(Trace $trace): TraceDetailObject
    {
        return new TraceDetailObject(
            id: $trace->_id,
            service: $trace->service
                ? new TraceServiceObject(
                    id: $trace->service->id,
                    name: $trace->service->name,
                )
                : null,
            traceId: $trace->tid,
            parentTraceId: $trace->ptid,
            type: $trace->tp,
            status: $trace->st,
            tags: $this->parseTagsFromDb($trace->tgs),
            data: (new TraceDataToObjectBuilder($trace->dt))->build(),
            duration: $trace->dur,
            memory: $trace->mem,
            cpu: $trace->cpu,
            hasProfiling: $trace->hpr ?? false,
            loggedAt: $trace->lat,
            createdAt: $trace->cat,
            updatedAt: $trace->uat
        );
    }

    private function makeTraceDtoFromDocument(array $document): TraceDto
    {
        return new TraceDto(
            id: $document['_id'],
            serviceId: $document['sid'],
            traceId: $document['tid'],
            parentTraceId: $document['ptid'],
            type: $document['tp'],
            status: $document['st'],
            tags: $this->parseTagsFromDb($document['tgs']),
            data: (new TraceDataToObjectBuilder($document['dt']))->build(),
            duration: $document['dur'],
            memory: $document['mem'],
            cpu: $document['cpu'],
            hasProfiling: $document['hpr'] ?? false,
            loggedAt: new Carbon($document['lat']->toDateTime()),
            createdAt: new Carbon($document['cat']->toDateTime()),
            updatedAt: new Carbon($document['uat']->toDateTime())
        );
    }
}
