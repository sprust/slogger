<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceTree;
use App\Modules\Trace\Repositories\Interfaces\CollectorTraceTreeRepositoryInterface;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;

class CollectorTraceTreeRepository implements CollectorTraceTreeRepositoryInterface
{
    public function insertMany(array $parametersList): void
    {
        $operations = [];

        $createdAt = new UTCDateTime(now());

        foreach ($parametersList as $parameters) {
            $operations[] = [
                'updateOne' => [
                    [
                        'traceId'       => $parameters->traceId,
                        'parentTraceId' => $parameters->parentTraceId,
                    ],
                    [
                        '$set'         => [
                            'traceId'       => $parameters->traceId,
                            'parentTraceId' => $parameters->parentTraceId,
                            'loggedAt'      => new UTCDateTime($parameters->loggedAt),
                        ],
                        '$setOnInsert' => [
                            'createdAt' => $createdAt,
                        ],
                    ],
                    [
                        'upsert' => true,
                    ],
                ],
            ];
        }

        TraceTree::collection()->bulkWrite($operations);
    }

    public function deleteMany(Carbon $to): void
    {
        TraceTree::query()->where('loggedAt', '<=', new UTCDateTime($to))->delete();
    }
}
