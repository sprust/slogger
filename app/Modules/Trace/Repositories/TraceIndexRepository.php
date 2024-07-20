<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceIndex;
use App\Modules\Trace\Repositories\Dto\TraceIndexDto;
use App\Modules\Trace\Repositories\Dto\TraceIndexFieldDto;
use App\Modules\Trace\Repositories\Interfaces\TraceIndexRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;

class TraceIndexRepository implements TraceIndexRepositoryInterface
{
    public function findOneOrCreate(array $fields, Carbon $actualUntilAt): ?TraceIndexDto
    {
        $name = $this->makeIndexName($fields);

        $createdAt = now();

        TraceIndex::collection()
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

    private function findOneByName(string $name): ?TraceIndexDto
    {
        /** @var TraceIndex|null $index */
        $index = TraceIndex::query()->where('name', $name)->first();

        if (!$index) {
            return null;
        }

        return new TraceIndexDto(
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
        return TraceIndex::query()
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
            ->map(fn(TraceIndex $index) => new TraceIndexDto(
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
        return (bool) TraceIndex::query()
            ->where('name', $name)
            ->update([
                'inProcess' => $inProcess,
                'created'   => $created,
            ]);
    }

    public function deleteByName(string $name): bool
    {
        return (bool) TraceIndex::query()
            ->where('name', $name)
            ->delete();
    }

    /**
     * @param TraceIndexFieldDto[] $fields
     *
     * @return string
     */
    private function makeIndexName(array $fields): string
    {
        return 'dynamic-' . md5(json_encode($fields));
    }

    /**
     * @param array $fields
     *
     * @return TraceIndexFieldDto[]
     */
    private function transportFields(array $fields): array
    {
        $result = [];

        foreach ($fields as $field) {
            $result[] = new TraceIndexFieldDto(
                fieldName: $field['fieldName'],
                isText: $field['isText'],
            );
        }

        return $result;
    }
}
