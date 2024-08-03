<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceDynamicIndex;
use App\Modules\Trace\Repositories\Dto\TraceDynamicIndexDto;
use App\Modules\Trace\Repositories\Dto\TraceDynamicIndexFieldDto;
use App\Modules\Trace\Repositories\Interfaces\TraceDynamicIndexRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;

class TraceDynamicIndexRepository implements TraceDynamicIndexRepositoryInterface
{
    public function findOneOrCreate(array $fields, Carbon $actualUntilAt): ?TraceDynamicIndexDto
    {
        $name = $this->makeIndexName($fields);

        $createdAt = now();

        TraceDynamicIndex::collection()
            ->updateOne(
                [
                    'name' => $name,
                ],
                [
                    '$set'         => [
                        'actualUntilAt' => new UTCDateTime($actualUntilAt),
                    ],
                    '$setOnInsert' => [
                        'fields'    => $fields,
                        'inProcess' => true,
                        'created'   => false,
                        'createdAt' => new UTCDateTime($createdAt),
                    ],
                ],
                [
                    'upsert' => true,
                ]
            );

        return $this->findOneByName($name);
    }

    private function findOneByName(string $name): ?TraceDynamicIndexDto
    {
        /** @var TraceDynamicIndex|null $index */
        $index = TraceDynamicIndex::query()->where('name', $name)->first();

        if (!$index) {
            return null;
        }

        return new TraceDynamicIndexDto(
            name: $name,
            fields: $this->transportFields($index->fields),
            inProcess: $index->inProcess,
            created: $index->created,
            actualUntilAt: $index->actualUntilAt,
            createdAt: $index->createdAt
        );
    }

    public function find(
        int $limit,
        ?bool $inProcess = null,
        bool $sortByCreatedAtAsc = false,
        ?Carbon $toActualUntilAt = null
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
            ->orderBy('createdAt')
            ->take($limit)
            ->get()
            ->map(fn(TraceDynamicIndex $index) => new TraceDynamicIndexDto(
                name: $index->name,
                fields: $this->transportFields($index->fields),
                inProcess: $index->inProcess,
                created: $index->created,
                actualUntilAt: $index->actualUntilAt,
                createdAt: $index->createdAt
            ))
            ->all();
    }

    public function updateByName(string $name, bool $inProcess, bool $created): bool
    {
        return (bool) TraceDynamicIndex::query()
            ->where('name', $name)
            ->update([
                'inProcess' => $inProcess,
                'created'   => $created,
            ]);
    }

    public function deleteByName(string $name): bool
    {
        return (bool) TraceDynamicIndex::query()
            ->where('name', $name)
            ->delete();
    }

    /**
     * @param TraceDynamicIndexFieldDto[] $fields
     *
     * @return string
     */
    private function makeIndexName(array $fields): string
    {
        return 'dynamic_' . md5(json_encode($fields));
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
}
