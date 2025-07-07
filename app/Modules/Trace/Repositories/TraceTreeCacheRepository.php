<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceTreeCache;
use App\Modules\Trace\Contracts\Repositories\TraceTreeCacheRepositoryInterface;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeStringableObject;
use App\Modules\Trace\Repositories\Dto\Trace\TraceTreeDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceTreeServiceDto;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONDocument;

class TraceTreeCacheRepository implements TraceTreeCacheRepositoryInterface
{
    public function has(string $parentTraceId): bool
    {
        return TraceTreeCache::query()
            ->where('parentTraceId', $parentTraceId)
            ->exists();
    }

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

    public function paginate(int $page, int $perPage, string $parentTraceId): array
    {
        $results = TraceTreeCache::collection()
            ->aggregate([
                [
                    '$match' => [
                        'parentTraceId' => $parentTraceId,
                    ],
                ],
                [
                    '$sort' => [
                        'order' => 1,
                    ],
                ],
                [
                    '$skip' => ($page - 1) * $perPage,
                ],
                [
                    '$limit' => $perPage,
                ],
            ]);

        return array_map(
            static fn(BSONDocument $item) => new TraceTreeDto(
                serviceId: $item['serviceId'],
                traceId: $item['traceId'],
                parentTraceId: $item['parentTraceId'],
                type: $item['type'],
                status: $item['status'],
                duration: $item['duration'],
                memory: $item['memory'],
                cpu: $item['cpu'],
                loggedAt: new Carbon($item['loggedAt']->toDateTime()),
                depth: $item['depth'],
            ),
            iterator_to_array($results)
        );
    }

    public function findServices(string $parentTraceId): array
    {
        $results = TraceTreeCache::collection()
            ->aggregate([
                [
                    '$match' => [
                        'parentTraceId' => $parentTraceId,
                    ],
                ],
                [
                    '$group' => [
                        '_id'   => '$serviceId',
                        'count' => ['$sum' => 1],
                    ],
                ],
            ]);

        $services = [];

        foreach ($results as $result) {
            $services[] = new TraceTreeServiceDto(
                id: $result->_id,
                tracesCount: $result->count,
            );
        }

        return $services;
    }

    public function findTypes(string $parentTraceId): array
    {
        $results = TraceTreeCache::collection()
            ->aggregate([
                [
                    '$match' => [
                        'parentTraceId' => $parentTraceId,
                    ],
                ],
                [
                    '$group' => [
                        '_id'   => '$type',
                        'count' => ['$sum' => 1],
                    ],
                ],
            ]);

        $types = [];

        foreach ($results as $result) {
            $types[] = new TraceTreeStringableObject(
                name: $result->_id,
                tracesCount: $result->count,
            );
        }

        return $types;
    }

    public function findStatuses(string $parentTraceId): array
    {
        $results = TraceTreeCache::collection()
            ->aggregate([
                [
                    '$match' => [
                        'parentTraceId' => $parentTraceId,
                    ],
                ],
                [
                    '$group' => [
                        '_id'   => '$status',
                        'count' => ['$sum' => 1],
                    ],
                ],
            ]);

        $statuses = [];

        foreach ($results as $result) {
            $statuses[] = new TraceTreeStringableObject(
                name: $result->_id,
                tracesCount: $result->count,
            );
        }

        return $statuses;
    }

    public function findCount(string $parentTraceId): int
    {
        return TraceTreeCache::query()
            ->where('parentTraceId', $parentTraceId)
            ->count();
    }
}
