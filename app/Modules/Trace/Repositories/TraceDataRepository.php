<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceData;
use App\Modules\Trace\Repositories\Interfaces\TraceDataRepositoryInterface;

class TraceDataRepository implements TraceDataRepositoryInterface
{
    public function syncMany(array $keys): void
    {
        $operations = array_map(
            fn(string $key) => [
                'updateOne' => [
                    [
                        'k' => $key,
                    ],
                    [
                        '$set' => [
                            'k' => $key,
                        ],
                    ],
                    [
                        'upsert' => true,
                    ],
                ],
            ],
            $keys
        );

        TraceData::collection()->bulkWrite($operations);
    }

    public function findIdByKey(string $key): ?string
    {
        return TraceData::query()
            ->where('k', $key)
            ->value('_id');
    }

    public function findByKeys(array $keys): array
    {
        $data = TraceData::query()
            ->whereIn('k', $keys)
            ->toBase()
            ->get();
    }
}
