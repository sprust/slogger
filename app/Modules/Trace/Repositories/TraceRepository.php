<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceDynamicIndex;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Entities\Trace\TraceCollectionNameObjects;
use App\Modules\Trace\Entities\Trace\TraceIndexInfoObject;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Repositories\Dto\Trace\Profiling\TraceProfilingDataDto;
use App\Modules\Trace\Repositories\Dto\Trace\Profiling\TraceProfilingDto;
use App\Modules\Trace\Repositories\Dto\Trace\Profiling\TraceProfilingItemDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceBufferDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDto;
use App\Modules\Trace\Repositories\Services\PeriodicTraceCollectionNameService;
use App\Modules\Trace\Repositories\Services\PeriodicTraceService;
use App\Modules\Trace\Repositories\Services\TraceDataToObjectBuilder;
use App\Modules\Trace\Repositories\Services\TracePipelineBuilder;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\Exception;
use RuntimeException;
use SParallel\Exceptions\ContextCheckerException;
use SParallel\SParallelWorkers;
use stdClass;
use Throwable;

readonly class TraceRepository implements TraceRepositoryInterface
{
    public function __construct(
        private PeriodicTraceCollectionNameService $periodicTraceCollectionNameService,
        private TracePipelineBuilder $tracePipelineBuilder,
        private PeriodicTraceService $periodicTraceService,
        private SParallelWorkers $parallelWorkers
    ) {
    }

    public function createManyByBuffers(array $traceBuffers): void
    {
        /**
         * @var array<string, TraceBufferDto[]> $collectionNameTraceBuffers
         */
        $collectionNameTraceBuffers = [];

        foreach ($traceBuffers as $traceBuffer) {
            $collectionName = $this->periodicTraceCollectionNameService->newByDatetime(
                datetime: $traceBuffer->loggedAt
            );

            $collectionNameTraceBuffers[$collectionName]   ??= [];
            $collectionNameTraceBuffers[$collectionName][] = $traceBuffer;
        }

        foreach ($collectionNameTraceBuffers as $collectionName => $collectionNameTraceBuffer) {
            $operations = [];

            foreach ($collectionNameTraceBuffer as $buffer) {
                $operations[] = [
                    'updateOne' => [
                        [
                            'sid' => $buffer->serviceId,
                            'tid' => $buffer->traceId,
                        ],
                        [
                            '$set' => [
                                'ptid' => $buffer->parentTraceId,
                                'tp'   => $buffer->type,
                                'st'   => $buffer->status,
                                'tgs'  => $this->prepareTagsForSave($buffer->tags),
                                'dt'   => $this->prepareData(json_decode($buffer->data, true)),
                                'dur'  => $buffer->duration,
                                'mem'  => $buffer->memory,
                                'cpu'  => $buffer->cpu,
                                'tss'  => $buffer->timestamps,
                                'lat'  => new UTCDateTime($buffer->loggedAt),
                                'uat'  => new UTCDateTime($buffer->updatedAt),
                                'cat'  => new UTCDateTime($buffer->createdAt),
                                'hpr'  => $buffer->hasProfiling,
                                'pr'   => $buffer->profiling,
                            ],
                        ],
                        [
                            'upsert' => true,
                        ],
                    ],
                ];
            }

            $this->periodicTraceService->initCollectionByName($collectionName)
                ->bulkWrite($operations);
        }
    }

    public function findOneDetailByTraceId(string $traceId): ?TraceDto
    {
        $collectionName = $this->periodicTraceService->findCollectionNameByTraceId($traceId);

        if (is_null($collectionName)) {
            return null;
        }

        $document = $this->periodicTraceService->findOne(
            collectionName: $collectionName,
            traceId: $traceId
        );

        if (!$document) {
            return null;
        }

        return $this->makeTraceDtoFromDocument($document);
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
    ): array {
        $collectionNames = $this->periodicTraceService->detectCollectionNamesReverse(
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo
        );

        if (!count($collectionNames)) {
            return [];
        }

        $pipeline = $this->tracePipelineBuilder->make(
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

        $pipeline[] = [
            '$sort' => [
                'lat' => -1,
                '_id' => 1,
            ],
        ];

        return $this->periodicTraceService->paginate(
            collectionNames: $collectionNames,
            pipeline: $pipeline,
            page: $page,
            perPage: $perPage,
            documentPreparer: function (string $collectionName, array $document): TraceDto {
                return $this->makeTraceDtoFromDocument($document);
            }
        );
    }

    public function findTraceIds(
        int $limit,
        ?Carbon $loggedAtTo = null,
        ?string $type = null,
        ?array $excludedTypes = null,
        ?bool $noCleared = null
    ): TraceCollectionNameObjects {
        $collectionNames = $this->periodicTraceService->detectCollectionNames(
            loggedAtTo: $loggedAtTo
        );

        if (!count($collectionNames)) {
            return new TraceCollectionNameObjects();
        }

        $customMatch = [];

        if (is_null($type) && !is_null($excludedTypes)) {
            $customMatch['tp'] = ['$nin' => $excludedTypes];
        }

        $pipeline = $this->tracePipelineBuilder->make(
            loggedAtTo: $loggedAtTo,
            types: is_null($type) ? [] : [$type],
            customMatch: count($customMatch) ? $customMatch : null
        );

        if ($noCleared) {
            $pipeline[] = [
                '$match' => [
                    '$or' => [
                        ['cl' => false],
                        ['cl' => ['$exists' => false]],
                    ],
                ],
            ];
        }

        $pipeline[] = [
            '$sort' => [
                'lat' => -1,
                '_id' => 1,
            ],
        ];

        $pipeline[] = [
            '$project' => [
                'tid' => 1,
            ],
        ];

        $traceCollectionNamesRaw = $this->periodicTraceService->paginate(
            collectionNames: $collectionNames,
            pipeline: $pipeline,
            page: 1,
            perPage: $limit,
            documentPreparer: static function (string $collectionName, array $document) {
                return [
                    'cn'  => $collectionName,
                    'tid' => $document['tid'],
                ];
            }
        );

        $traceCollectionNames = [];

        foreach ($traceCollectionNamesRaw as $item) {
            $traceCollectionNames[$item['cn']]   ??= [];
            $traceCollectionNames[$item['cn']][] = $item['tid'];
        }

        $result = new TraceCollectionNameObjects();

        foreach ($traceCollectionNames as $collectionName => $traceIds) {
            $result->add($collectionName, $traceIds);
        }

        return $result;
    }

    public function findByTraceIds(array $traceIds): array
    {
        /** @var TraceDto[] $traces */
        $traces = [];

        $collectionNames = $this->periodicTraceService->findCollectionNamesByTraceIds($traceIds);

        foreach ($collectionNames->get() as $collectionName => $collectionTraceIds) {
            $documents = $this->periodicTraceService->findMany(
                collectionName: $collectionName,
                traceIds: $collectionTraceIds
            );

            foreach ($documents as $document) {
                $traces[] = $this->makeTraceDtoFromDocument($document);
            }
        }

        return $traces;
    }

    public function findProfilingByTraceId(string $traceId): ?TraceProfilingDto
    {
        $trace = $this->periodicTraceService->findOne(
            collectionName: $this->periodicTraceService->findCollectionNameByTraceId($traceId),
            traceId: $traceId
        );

        $profilingData = is_null($trace) ? null : ($trace['pr'] ?? null);

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
        string $collectionName,
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        ?string $type = null,
        ?array $excludedTypes = null
    ): int {
        $collection = $this->periodicTraceService->selectCollectionByName($collectionName);

        $lat = [];

        if (!is_null($loggedAtFrom)) {
            $lat['$gte'] = new UTCDateTime($loggedAtFrom);
        }

        if (!is_null($loggedAtTo)) {
            $lat['$lte'] = new UTCDateTime($loggedAtTo);
        }

        $result = $collection->deleteMany(
            [
                ...(count($traceIds) ? ['tid' => ['$in' => $traceIds]] : []),
                ...(count($lat) ? ['lat' => $lat] : []),
                ...(is_null($type) ? [] : ['tp' => $type]),
                ...((is_null($type) && !is_null($excludedTypes))
                    ? ['tp' => ['$nin' => $excludedTypes]]
                    : []
                ),
            ],
        );

        return $result->getDeletedCount();
    }

    public function clearTraces(
        string $collectionName,
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        ?string $type = null,
        ?array $excludedTypes = null
    ): int {
        $collection = $this->periodicTraceService->selectCollectionByName($collectionName);

        $lat = [];

        if (!is_null($loggedAtFrom)) {
            $lat['$gte'] = new UTCDateTime($loggedAtFrom);
        }

        if (!is_null($loggedAtTo)) {
            $lat['$lte'] = new UTCDateTime($loggedAtTo);
        }

        $result = $collection->updateMany(
            [
                ...(count($traceIds) ? ['tid' => ['$in' => $traceIds]] : []),
                ...(count($lat) ? ['lat' => $lat] : []),
                ...(is_null($type) ? [] : ['tp' => $type]),
                ...((is_null($type) && !is_null($excludedTypes)) ? ['tp' => ['$nin' => $excludedTypes]] : []),
                '$or' => [
                    ['cl' => false],
                    ['cl' => ['$exists' => false]],
                ],
            ],
            [
                '$set' => [
                    'dt'  => new stdClass(),
                    'pr'  => null,
                    'hpr' => false,
                    'cl'  => true,
                ],
            ]
        );

        return $result->getModifiedCount();
    }

    /**
     * @throws ContextCheckerException
     */
    public function createIndex(string $name, array $collectionNames, array $fields): bool
    {
        if (empty($collectionNames) || empty($fields)) {
            return false;
        }

        /** @var array<string, int> $index */
        $index = [];

        foreach ($fields as $field) {
            $index[$field->fieldName] = 1;
        }

        /** @var Closure[] $callbacks */
        $callbacks = [];

        foreach ($collectionNames as $collectionName) {
            $callbacks[] = static function (PeriodicTraceService $periodicTraceService) use (
                $name,
                $index,
                $collectionName
            ) {
                try {
                    $periodicTraceService->createIndex(
                        indexName: $name,
                        collectionName: $collectionName,
                        index: $index
                    );
                } catch (Throwable $exception) {
                    if (str_contains($exception->getMessage(), 'already exists')) {
                        return;
                    }

                    throw $exception;
                }
            };
        }

        $results = $this->parallelWorkers->wait(
            callbacks: $callbacks,
            timeoutSeconds: 600, // 10 minutes // TODO: smart calculation
            workersLimit: 6,
            breakAtFirstError: true
        );

        if ($results->hasFailed()) {
            $failedResult = $results->getFailed()[0] ?? null;

            if ($failedResult?->exception) {
                throw $failedResult->exception;
            }

            throw new RuntimeException(
                message: 'unknown error',
            );
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function getIndexProgressesInfo(): array
    {
        $operations = TraceDynamicIndex::collection()->getManager()
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
            $progressTotal = $operation->progress?->total;
            $progressDone  = $operation->progress?->done;

            $infoList[] = new TraceIndexInfoObject(
                collectionName: $operation->command->createIndexes,
                name: $operation->command->indexes[0]?->name ?? 'untitled',
                progress: $progressTotal ? round($progressDone / $progressTotal * 100, 2) : 0
            );
        }

        return $infoList;
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

    public function deleteEmptyCollections(Carbon $loggedAtTo): void
    {
        $collectionNames = $this->periodicTraceService->detectCollectionNames(
            loggedAtTo: $loggedAtTo
        );

        if (!count($collectionNames)) {
            return;
        }

        foreach ($collectionNames as $collectionName) {
            $collection = $this->periodicTraceService->selectCollectionByName($collectionName);

            $collStats = iterator_to_array(
                $collection->aggregate([
                    [
                        '$collStats' => [
                            'storageStats' => (object) [],
                        ],
                    ],
                ])
            )[0];

            $documentsCount = $collStats['storageStats']['count'] ?? null;

            if ($documentsCount) {
                continue;
            }

            $collection->drop();
        }
    }

    /**
     * @param string[] $tags
     *
     * @return array<string, string>[]
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
     * @param array<string|int, mixed> $data
     *
     * @return array<string|int, mixed>
     */
    private function prepareData(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $this->prepareDataRecursive($result, $key, $value);
        }

        return $result;
    }

    /**
     * @param array<string|int, mixed> $result
     */
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

    /**
     * @param array<string, mixed> $document
     */
    private function makeTraceDtoFromDocument(array $document): TraceDto
    {
        return new TraceDto(
            id: (string) $document['_id'],
            serviceId: $document['sid'],
            traceId: $document['tid'],
            parentTraceId: $document['ptid'],
            type: $document['tp'],
            status: $document['st'],
            tags: array_map(
                static fn(array $tag) => $tag['nm'],
                $document['tgs']
            ),
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
