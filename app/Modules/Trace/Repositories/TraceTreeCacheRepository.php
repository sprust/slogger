<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceTreeCache;
use App\Modules\Trace\Contracts\Repositories\TraceTreeCacheRepositoryInterface;
use MongoDB\BSON\UTCDateTime;

class TraceTreeCacheRepository implements TraceTreeCacheRepositoryInterface
{
    public function deleteByParentTraceId(string $parentTraceId): void
    {
        TraceTreeCache::query()
            ->where('parentTraceId', $parentTraceId)
            ->delete();
    }

    public function createMany(string $parentTraceId, array $parametersList): void
    {
        $operations = [];

        $createdAt = new UTCDateTime(now());

        foreach ($parametersList as $parameters) {
            $operations[] = [
                'insertOne' => [
                    [
                        'parentTraceId' => $parentTraceId,
                        'traceId'       => $parameters->traceId,
                        'serviceId'     => $parameters->serviceId,
                        'type'          => $parameters->type,
                        'status'        => $parameters->status,
                        'duration'      => $parameters->duration,
                        'memory'        => $parameters->memory,
                        'cpu'           => $parameters->cpu,
                        'order'         => null,
                        'depth'         => null,
                        'loggedAt'      => new UTCDateTime($parameters->loggedAt),
                        'createdAt'     => $createdAt,
                    ],
                ],
            ];
        }

        TraceTreeCache::collection()->bulkWrite($operations);
    }

    public function updateDepths(string $parentTraceId, array $depths): void
    {
        $operations = [];

        foreach ($depths as $traceId => $depth) {
            $operations[] = [
                'updateOne' => [
                    [
                        'parentTraceId' => $parentTraceId,
                        'traceId'       => $traceId,
                    ],
                    [
                        '$set' => [
                            'order' => $depth->order,
                            'depth' => $depth->depth,
                        ],
                    ],
                ],
            ];
        }

        TraceTreeCache::collection()->bulkWrite($operations);
    }
}
