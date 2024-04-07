<?php

namespace App\Modules\TraceCollector\Repositories;

use App\Models\Traces\TraceTree;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceTreeDeleteManyParameters;
use App\Modules\TraceCollector\Repositories\Interfaces\TraceTreeRepositoryInterface;
use MongoDB\BSON\UTCDateTime;

class TraceTreeRepository implements TraceTreeRepositoryInterface
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

    public function deleteMany(TraceTreeDeleteManyParameters $parameters): void
    {
        TraceTree::query()->where('loggedAt', '<=', new UTCDateTime($parameters->to))->delete();
    }
}
