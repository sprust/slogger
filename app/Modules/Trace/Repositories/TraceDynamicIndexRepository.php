<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceDynamicIndex;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexDataDto;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexDto;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexFieldDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDynamicIndexStatsDto;
use App\Modules\Trace\Repositories\Services\PeriodicTraceService;
use Illuminate\Support\Carbon;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Throwable;

readonly class TraceDynamicIndexRepository
{
    public function __construct(private PeriodicTraceService $periodicTraceService)
    {
    }

    public function findOneOrCreate(TraceDynamicIndexDataDto $indexData, Carbon $actualUntilAt): ?TraceDynamicIndexDto
    {
        $collectionsNames = $this->periodicTraceService->detectCollectionNames(
            loggedAtFrom: $indexData->loggedAtFrom,
            loggedAtTo: $indexData->loggedAtTo
        );

        $fieldsName      = $this->makeFieldsName($indexData->fields);
        $collectionsName = $this->makeCollectionsName($collectionsNames);

        $indexName = "dyn_{$fieldsName}_$collectionsName";

        $createdAt = now();

        TraceDynamicIndex::sconcur()
            ->updateOne(
                filter: [
                    'name' => $indexName,
                ],
                update: [
                    '$set'         => [
                        'actualUntilAt' => new UTCDateTime($actualUntilAt),
                    ],
                    '$setOnInsert' => [
                        'indexName'       => "dyn_$fieldsName",
                        'collectionNames' => $collectionsNames,
                        'fields'          => $indexData->fields,
                        'inProcess'       => true,
                        'created'         => false,
                        'error'           => null,
                        'createdAt'       => new UTCDateTime($createdAt),
                    ],
                ],
                upsert: true,
            );

        return $this->findOneByName($indexName);
    }

    public function findOneById(string $id): ?TraceDynamicIndexDto
    {
        $document = TraceDynamicIndex::sconcur()->findOne(['_id' => new ObjectId($id)]);

        if (!$document) {
            return null;
        }

        return $this->documentToDto($document);
    }

    /**
     * @return TraceDynamicIndexDto[]
     */
    public function find(
        int $limit,
        ?bool $inProcess = null,
        ?Carbon $toActualUntilAt = null,
        bool $orderByCreatedAtDesc = false,
    ): array {
        $filter = [];

        if (!is_null($inProcess)) {
            $filter['inProcess'] = $inProcess;
        }

        if (!is_null($toActualUntilAt)) {
            $filter['actualUntilAt'] = ['$lt' => new UTCDateTime($toActualUntilAt)];
        }

        $cursor = TraceDynamicIndex::sconcur()->find(
            filter: $filter,
            sort: ['createdAt' => $orderByCreatedAtDesc ? -1 : 1],
            limit: $limit,
        );

        $result = [];

        foreach ($cursor as $document) {
            $result[] = $this->documentToDto($document);
        }

        return $result;
    }

    public function findStats(): TraceDynamicIndexStatsDto
    {
        $cursor = TraceDynamicIndex::sconcur()
            ->aggregate(
                [
                    [
                        '$group' => [
                            '_id'       => null,
                            'total'     => [
                                '$sum' => 1,
                            ],
                            'inProcess' => [
                                '$sum' => [
                                    '$cond' => [['$eq' => ['$inProcess', true]], 1, 0],
                                ],
                            ],
                            'errors'    => [
                                '$sum' => [
                                    '$cond' => [['$eq' => ['$error', null]], 0, 1],
                                ],
                            ],
                        ],
                    ],
                ]
            );

        $stats = iterator_to_array($cursor)[0] ?? null;

        return new TraceDynamicIndexStatsDto(
            inProcessCount: $stats['inProcess'] ?? 0,
            errorsCount: $stats['errors'] ?? 0,
            totalCount: $stats['total'] ?? 0,
        );
    }

    public function updateByName(string $name, bool $inProcess, bool $created, ?Throwable $exception): bool
    {
        return TraceDynamicIndex::sconcur()
            ->updateMany(
                filter: ['name' => $name],
                update: [
                    '$set' => [
                        'inProcess' => $inProcess,
                        'created'   => $created,
                        'error'     => $exception
                            ? $exception::class . ": {$exception->getMessage()}"
                            : null,
                    ],
                ],
            )
            ->matchedCount > 0;
    }

    public function deleteById(string $id): bool
    {
        return TraceDynamicIndex::sconcur()
            ->deleteOne(['_id' => new ObjectId($id)])
            ->deletedCount > 0;
    }

    private function findOneByName(string $name): ?TraceDynamicIndexDto
    {
        $document = TraceDynamicIndex::sconcur()->findOne(['name' => $name]);

        if (!$document) {
            return null;
        }

        return $this->documentToDto($document);
    }

    /**
     * @param TraceDynamicIndexFieldDto[] $fields
     */
    private function makeFieldsName(array $fields): string
    {
        return implode(
            '__',
            array_map(
                fn(TraceDynamicIndexFieldDto $dto) => $dto->fieldName,
                $fields
            )
        );
    }

    /**
     * @param string[] $collectionNames
     */
    private function makeCollectionsName(array $collectionNames): string
    {
        return implode('__', $collectionNames);
    }

    /**
     * @param array<int|string, mixed> $document
     */
    private function documentToDto(array $document): TraceDynamicIndexDto
    {
        return new TraceDynamicIndexDto(
            id: (string) $document['_id'],
            name: $document['name'],
            indexName: $document['indexName'],
            collectionNames: (array) $document['collectionNames'],
            fields: array_map(
                static fn(array $field) => new TraceDynamicIndexFieldDto(
                    fieldName: $field['fieldName']
                ),
                (array) $document['fields']
            ),
            inProcess: $document['inProcess'],
            created: $document['created'],
            error: $document['error'],
            actualUntilAt: new Carbon($document['actualUntilAt']->toDateTime()),
            createdAt: new Carbon($document['createdAt']->toDateTime()),
        );
    }
}
