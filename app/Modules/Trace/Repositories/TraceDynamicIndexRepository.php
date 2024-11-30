<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceDynamicIndex;
use App\Modules\Trace\Contracts\Repositories\TraceDynamicIndexRepositoryInterface;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexDto;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexesDto;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexFieldDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDynamicIndexStatsDto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;
use Throwable;

class TraceDynamicIndexRepository implements TraceDynamicIndexRepositoryInterface
{
    public function findOneOrCreate(TraceDynamicIndexesDto $fields, Carbon $actualUntilAt): ?TraceDynamicIndexDto
    {
        $fieldsKey = $this->makeFieldsKey($fields->fieldNames);

        /** @var TraceDynamicIndex|null $index */
        $index = TraceDynamicIndex::query()
            ->where('fieldsKey', $fieldsKey)
            ->when(
                value: is_null($fields->loggedAtFrom),
                callback: static fn(Builder $builder) => $builder->whereNull('loggedAtFrom'),
                default: static fn(Builder $builder) => $builder->where(
                    'loggedAtFrom',
                    '<=',
                    new UTCDateTime($fields->loggedAtFrom)
                )
            )
            ->when(
                value: is_null($fields->loggedAtTo),
                callback: static fn(Builder $builder) => $builder->whereNull('loggedAtTo'),
                default: static fn(Builder $builder) => $builder->where(
                    'loggedAtTo',
                    '<=',
                    new UTCDateTime($fields->loggedAtTo)
                )
            )
            ->first();

        if (!$index) {
            $indexName = $fieldsKey;

            if ($fields->loggedAtFrom || $fields->loggedAtTo) {
                $loggedAtFromView = $fields->loggedAtFrom?->toDateTimeString();
                $loggedAtFromView = $loggedAtFromView ? "latFrom_$loggedAtFromView" : null;

                $loggedAtToView = $fields->loggedAtTo?->toDateTimeString();
                $loggedAtToView = $loggedAtToView ? "latTo_$loggedAtToView" : null;

                $indexName .= ('__' . implode('_', array_filter([$loggedAtFromView, $loggedAtToView])));
            }

            $index = new TraceDynamicIndex();

            $index->indexName    = $indexName;
            $index->fieldsKey    = $fieldsKey;
            $index->loggedAtFrom = $fields->loggedAtFrom;
            $index->loggedAtTo   = $fields->loggedAtTo;
            $index->fields       = $fields->fieldNames;
            $index->inProcess    = true;
            $index->created      = false;
            $index->error        = null;
        }

        $index->actualUntilAt = $actualUntilAt;
        $index->save();

        return $this->modelToDto($index);
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

    public function updateById(string $id, bool $inProcess, bool $created, ?Throwable $exception): bool
    {
        return (bool) TraceDynamicIndex::query()
            ->where('_id', $id)
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
     * @param TraceDynamicIndexFieldDto[] $fieldNames
     */
    private function makeFieldsKey(array $fieldNames): string
    {
        $fieldNamesView = implode(
            '__',
            array_map(
                fn(TraceDynamicIndexFieldDto $dto) => $dto->fieldName,
                $fieldNames
            )
        );

        return "dyn_$fieldNamesView";
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
                fieldName: ((array) $field)['fieldName']
            );
        }

        return $result;
    }

    private function modelToDto(TraceDynamicIndex $index): TraceDynamicIndexDto
    {
        return new TraceDynamicIndexDto(
            id: $index->_id,
            indexName: $index->indexName,
            fieldsKey: $index->fieldsKey,
            loggedAtFrom: $index->loggedAtFrom,
            loggedAtTo: $index->loggedAtTo,
            fields: $this->transportFields($index->fields),
            inProcess: $index->inProcess,
            created: $index->created,
            error: $index->error,
            actualUntilAt: $index->actualUntilAt,
            createdAt: $index->createdAt
        );
    }
}
