<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceTreeCache;
use App\Modules\Trace\Contracts\Repositories\TraceTreeCacheRepositoryInterface;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawIterator;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeStringableObject;
use App\Modules\Trace\Repositories\Dto\Trace\TraceTreeServiceDto;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONDocument;

class TraceTreeCacheRepository implements TraceTreeCacheRepositoryInterface
{
    public function has(string $rootTraceId): bool
    {
        return TraceTreeCache::query()
            ->where('rootTraceId', $rootTraceId)
            ->exists();
    }

    public function delete(string $rootTraceId): void
    {
        TraceTreeCache::query()
            ->where('rootTraceId', $rootTraceId)
            ->delete();
    }

    public function createMany(string $rootTraceId, array $parametersList): void
    {
        $operations = [];

        $createdAt = new UTCDateTime(now());

        foreach ($parametersList as $parameters) {
            $operations[] = [
                'insertOne' => [
                    [
                        'rootTraceId'   => $rootTraceId,
                        'parentTraceId' => $parameters->parentTraceId,
                        'traceId'       => $parameters->traceId,
                        'serviceId'     => $parameters->serviceId,
                        'type'          => $parameters->type,
                        'tags'          => $parameters->tags,
                        'status'        => $parameters->status,
                        'duration'      => $parameters->duration,
                        'memory'        => $parameters->memory,
                        'cpu'           => $parameters->cpu,
                        'loggedAt'      => new UTCDateTime($parameters->loggedAt),
                        'createdAt'     => $createdAt,
                    ],
                ],
            ];
        }

        TraceTreeCache::collection()->bulkWrite($operations);
    }

    public function findMany(string $rootTraceId): TraceTreeRawIterator
    {
        $cursor = TraceTreeCache::collection()
            ->aggregate([
                [
                    '$match' => [
                        'rootTraceId' => $rootTraceId,
                    ],
                ],
            ]);

        return new TraceTreeRawIterator(
            transport: static fn(BSONDocument $item): TraceTreeRawObject => new TraceTreeRawObject(
                serviceId: $item['serviceId'],
                traceId: $item['traceId'],
                parentTraceId: $item['parentTraceId'],
                type: $item['type'],
                tags: (array) $item['tags'],
                status: $item['status'],
                duration: $item['duration'],
                memory: $item['memory'],
                cpu: $item['cpu'],
                loggedAt: new Carbon($item['loggedAt']->toDateTime()),
            ),
            iterator: $cursor
        );
    }

    public function findServices(string $rootTraceId): array
    {
        $results = TraceTreeCache::collection()
            ->aggregate([
                [
                    '$match' => [
                        'rootTraceId' => $rootTraceId,
                    ],
                ],
                [
                    '$group' => [
                        '_id'   => '$serviceId',
                        'count' => ['$sum' => 1],
                    ],
                ],
                [
                    '$sort' => [
                        'count' => -1,
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

    public function findTypes(string $rootTraceId): array
    {
        $results = TraceTreeCache::collection()
            ->aggregate([
                [
                    '$match' => [
                        'rootTraceId' => $rootTraceId,
                    ],
                ],
                [
                    '$group' => [
                        '_id'   => '$type',
                        'count' => ['$sum' => 1],
                    ],
                ],
                [
                    '$sort' => [
                        'count' => -1,
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

    public function findTags(string $rootTraceId): array
    {
        $results = TraceTreeCache::collection()
            ->aggregate([
                [
                    '$match' => [
                        'rootTraceId' => $rootTraceId,
                    ],
                ],
                [
                    '$unwind' => '$tags',
                ],
                [
                    '$group' => [
                        '_id'   => '$tags',
                        'count' => ['$sum' => 1],
                    ],
                ],
                [
                    '$sort' => [
                        'count' => -1,
                    ],
                ],
            ]);

        $tags = [];

        foreach ($results as $result) {
            $tags[] = new TraceTreeStringableObject(
                name: $result->_id,
                tracesCount: $result->count,
            );
        }

        return $tags;
    }

    public function findStatuses(string $rootTraceId): array
    {
        $results = TraceTreeCache::collection()
            ->aggregate([
                [
                    '$match' => [
                        'rootTraceId' => $rootTraceId,
                    ],
                ],
                [
                    '$group' => [
                        '_id'   => '$status',
                        'count' => ['$sum' => 1],
                    ],
                ],
                [
                    '$sort' => [
                        'count' => -1,
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

    public function findCount(string $rootTraceId): int
    {
        return TraceTreeCache::query()
            ->where('rootTraceId', $rootTraceId)
            ->count();
    }
}
