<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceDynamicIndex;
use App\Modules\Trace\Entities\Trace\DeletedTracesObject;
use App\Modules\Trace\Entities\Trace\TraceCollectionNameObjects;
use App\Modules\Trace\Entities\Trace\TraceIndexInfoObject;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexFieldDto;
use App\Modules\Trace\Repositories\Dto\Trace\Profiling\TraceProfilingDataDto;
use App\Modules\Trace\Repositories\Dto\Trace\Profiling\TraceProfilingDto;
use App\Modules\Trace\Repositories\Dto\Trace\Profiling\TraceProfilingItemDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDto;
use App\Modules\Trace\Repositories\Services\PeriodicTraceService;
use App\Modules\Trace\Repositories\Services\TraceDataToObjectBuilder;
use App\Modules\Trace\Repositories\Services\TracePipelineBuilder;
use Illuminate\Support\Carbon;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\Exception;
use RuntimeException;
use SConcur\Features\Mongodb\Types\ObjectId;
use SConcur\WaitGroup;
use Throwable;

readonly class TraceRepository
{
    public function __construct(
        private TracePipelineBuilder $tracePipelineBuilder,
        private PeriodicTraceService $periodicTraceService,
    ) {
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

    /**
     * @param string[]|null $excludedTypes
     */
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
            $traceCollectionNames[$item['cn']] ??= [];
            $traceCollectionNames[$item['cn']][] = $item['tid'];
        }

        $result = new TraceCollectionNameObjects();

        foreach ($traceCollectionNames as $collectionName => $traceIds) {
            $result->add($collectionName, $traceIds);
        }

        return $result;
    }

    /**
     * @param string[] $traceIds
     *
     * @return TraceDto[]
     */
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
        $collectionName = $this->periodicTraceService->findCollectionNameByTraceId($traceId);

        if ($collectionName === null) {
            return null;
        }

        $trace = $this->periodicTraceService->findOne(
            collectionName: $collectionName,
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

    /**
     * @param string[]                    $collectionNames
     * @param TraceDynamicIndexFieldDto[] $fields
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

        $waitGroup = WaitGroup::create();

        $periodicTraceService = $this->periodicTraceService;

        foreach ($collectionNames as $collectionName) {
            $waitGroup->add(
                static function () use (
                    $periodicTraceService,
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
                }
            );
        }

        // TODO: timeout?
        $waitGroup->waitAll();

        return true;
    }

    /**
     * @return TraceIndexInfoObject[]
     */
    public function getIndexProgressesInfo(): array
    {
        try {
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
        } catch (Exception $exception) {
            throw new RuntimeException(
                message: $exception->getMessage(),
                previous: $exception
            );
        }

        /** @var object[] $operations */
        $operations = iterator_to_array($operations)[0]->inprog ?? [];

        $infoList = [];

        foreach ($operations as $operation) {
            $progressTotal = $operation->progress->total ?? null;
            $progressDone  = $operation->progress->done ?? null;

            $infoList[] = new TraceIndexInfoObject(
                collectionName: $operation->command->createIndexes ?? 'undefined',
                name: ($operation->command->indexes[0] ?? null)?->name ?: 'untitled',
                progress: ($progressTotal && $progressDone) ? round($progressDone / $progressTotal * 100, 2) : 0
            );
        }

        return $infoList;
    }

    /**
     * @param string[] $collectionNames
     */
    public function deleteIndexByName(string $indexName, array $collectionNames): void
    {
        foreach ($collectionNames as $collectionName) {
            $this->periodicTraceService->deleteIndex(
                collectionName: $collectionName,
                indexName: $indexName
            );
        }
    }

    public function deleteCollections(Carbon $loggedAtTo): DeletedTracesObject
    {
        $collectionNames = $this->periodicTraceService->detectCollectionNames(
            loggedAtTo: $loggedAtTo
        );

        if (count($collectionNames) === 0) {
            return new DeletedTracesObject(
                collectionsCount: 0,
                tracesCount: 0,
            );
        }

        $collectionsCount = 0;
        $tracesCount      = 0;

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

            $collection->drop();

            ++$collectionsCount;
            $tracesCount += $documentsCount;
        }

        return new DeletedTracesObject(
            collectionsCount: $collectionsCount,
            tracesCount: $tracesCount,
        );
    }

    /**
     * @param array<string, mixed> $document
     */
    private function makeTraceDtoFromDocument(array $document): TraceDto
    {
        /** @var ObjectId $objectId */
        $objectId = $document['_id'];

        return new TraceDto(
            id: $objectId->id,
            serviceId: $document['sid'] ? ((int) $document['sid']) : null,
            traceId: $document['tid'],
            parentTraceId: $document['ptid'],
            type: $document['tp'],
            status: $document['st'],
            tags: array_map(
                static fn(string|array $tag) => is_array($tag) ? $tag['nm'] : $tag,
                $document['tgs']
            ),
            data: new TraceDataToObjectBuilder($document['dt'])->build(),
            duration: $document['dur'],
            memory: $document['mem'],
            cpu: $document['cpu'],
            hasProfiling: $document['hpr'] ?? false,
            loggedAt: new Carbon($document['lat']->dateTime),
            createdAt: new Carbon($document['cat']->dateTime),
            updatedAt: new Carbon($document['uat']->dateTime)
        );
    }
}
