<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceDynamicIndex;
use App\Modules\Trace\Contracts\Repositories\TraceDynamicIndexRepositoryInterface;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexDataDto;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexDto;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexFieldDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDynamicIndexStatsDto;
use App\Modules\Trace\Repositories\Services\PeriodicTraceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;
use Throwable;

readonly class TraceDynamicIndexRepository implements TraceDynamicIndexRepositoryInterface
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

        TraceDynamicIndex::collection()
            ->updateOne(
                [
                    'name' => $indexName,
                ],
                [
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
                [
                    'upsert' => true,
                ]
            );

        return $this->findOneByName($indexName);
    }

    public function findOneById(string $id): ?TraceDynamicIndexDto
    {
        /** @var TraceDynamicIndex|null $index */
        $index = TraceDynamicIndex::query()->find($id);

        if (!$index) {
            return null;
        }

        return $this->modelToDto($index);
    }

    private function findOneByName(string $name): ?TraceDynamicIndexDto
    {
        /** @var TraceDynamicIndex|null $index */
        $index = TraceDynamicIndex::query()->where('name', $name)->first();

        if (!$index) {
            return null;
        }

        return $this->modelToDto($index);
    }

    public function find(
        int $limit,
        ?bool $inProcess = null,
        bool $sortByCreatedAtAsc = false,
        ?Carbon $toActualUntilAt = null,
        bool $orderByCreatedAtDesc = false,
    ): array {
        return TraceDynamicIndex::query()
            ->when(
                !is_null($inProcess),
                fn(Builder $builder) => $builder->where('inProcess', $inProcess)
            )
            ->when(
                !is_null($toActualUntilAt),
                fn(Builder $builder) => $builder->where('actualUntilAt', '<', $toActualUntilAt)
            )
            ->when(
                value: $orderByCreatedAtDesc,
                callback: fn(Builder $builder) => $builder->orderByDesc('createdAt'),
                default: fn(Builder $builder) => $builder->orderBy('createdAt'),
            )
            ->take($limit)
            ->get()
            ->map(fn(TraceDynamicIndex $index) => $this->modelToDto($index))
            ->all();
    }

    public function findStats(): TraceDynamicIndexStatsDto
    {
        $cursor = TraceDynamicIndex::collection()
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

        $stats = collect($cursor)->first();

        return new TraceDynamicIndexStatsDto(
            inProcessCount: $stats?->inProcess ?? 0,
            errorsCount: $stats?->errors ?? 0,
            totalCount: $stats?->total ?? 0,
        );
    }

    public function updateByName(string $name, bool $inProcess, bool $created, ?Throwable $exception): bool
    {
        return (bool) TraceDynamicIndex::query()
            ->where('name', $name)
            ->update([
                'inProcess' => $inProcess,
                'created'   => $created,
                'error'     => $exception
                    ? $exception::class . ": {$exception->getMessage()}"
                    : null,
            ]);
    }

    public function deleteById(string $id): bool
    {
        return (bool) TraceDynamicIndex::query()
            ->where('_id', $id)
            ->delete();
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
     * @param array $fields
     *
     * @return TraceDynamicIndexFieldDto[]
     */
    private function transportFields(array $fields): array
    {
        $result = [];

        foreach ($fields as $field) {
            $result[] = new TraceDynamicIndexFieldDto(
                fieldName: $field['fieldName']
            );
        }

        return $result;
    }

    private function modelToDto(TraceDynamicIndex $index): TraceDynamicIndexDto
    {
        return new TraceDynamicIndexDto(
            id: $index->_id,
            name: $index->name,
            indexName: $index->indexName,
            collectionNames: $index->collectionNames,
            fields: $this->transportFields($index->fields),
            inProcess: $index->inProcess,
            created: $index->created,
            error: $index->error,
            actualUntilAt: $index->actualUntilAt,
            createdAt: $index->createdAt
        );
    }
}
