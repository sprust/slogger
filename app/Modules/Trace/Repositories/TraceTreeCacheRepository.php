<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceTreeCache;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawIterator;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeStringableObject;
use App\Modules\Trace\Parameters\CreateTraceTreeCacheParameters;
use App\Modules\Trace\Repositories\Dto\Trace\Tree\TraceTreeCachePageDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceTreeServiceDto;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use SConcur\Bson\ObjectId;
use SConcur\Bson\UTCDateTime;

class TraceTreeCacheRepository
{
    public function deleteChunk(string $rootTraceId, int $limit): int
    {
        if ($limit <= 0) {
            throw new InvalidArgumentException('Limit must be greater than 0');
        }

        $cursor = TraceTreeCache::sconcur()
            ->find(
                filter: ['rootTraceId' => $rootTraceId],
                projection: ['_id' => 1],
                limit: $limit,
                batchSize: $limit
            );

        $ids = [];

        foreach ($cursor as $item) {
            $ids[] = $item['_id'];
        }

        if ($ids === []) {
            return 0;
        }

        TraceTreeCache::sconcur()->deleteMany(['_id' => ['$in' => $ids]]);

        return count($ids);
    }

    /**
     * @param CreateTraceTreeCacheParameters[] $parametersList
     */
    public function createMany(string $rootTraceId, int $depth, array $parametersList): void
    {
        $operations = [];

        $createdAt = new UTCDateTime();

        foreach ($parametersList as $parameters) {
            $operations[] = [
                'updateOne' => [
                    [
                        'rootTraceId' => $rootTraceId,
                        'traceId'     => $parameters->traceId,
                    ],
                    [
                        '$set'         => [
                            'parentTraceId' => $parameters->parentTraceId,
                            'serviceId'     => $parameters->serviceId,
                            'type'          => $parameters->type,
                            'tags'          => $parameters->tags,
                            'status'        => $parameters->status,
                            'duration'      => $parameters->duration,
                            'memory'        => $parameters->memory,
                            'cpu'           => $parameters->cpu,
                            'loggedAt'      => new UTCDateTime($parameters->loggedAt),
                        ],
                        '$setOnInsert' => [
                            'depth'     => $depth,
                            'createdAt' => $createdAt,
                        ],
                    ],
                    [
                        'upsert' => true,
                    ],
                ],
            ];
        }

        TraceTreeCache::sconcur()->bulkWrite($operations);
    }

    public function existsByDepth(string $rootTraceId, int $depth): bool
    {
        return TraceTreeCache::sconcur()->findOne(
            filter: [
                'rootTraceId' => $rootTraceId,
                'depth'       => $depth,
            ],
            projection: ['_id' => 1]
        ) !== null;
    }

    public function findTraceIdsPage(
        string $rootTraceId,
        int $depth,
        ?string $afterId,
        int $limit
    ): TraceTreeCachePageDto {
        if ($limit <= 0) {
            throw new InvalidArgumentException('Limit must be greater than 0');
        }

        $filter = [
            'rootTraceId' => $rootTraceId,
            'depth'       => $depth,
        ];

        if (!is_null($afterId)) {
            $filter['_id'] = ['$gt' => new ObjectId($afterId)];
        }

        $cursor = TraceTreeCache::sconcur()
            ->find(
                filter: $filter,
                projection: [
                    '_id'     => 1,
                    'traceId' => 1,
                ],
                sort: ['_id' => 1],
                limit: $limit,
                batchSize: $limit
            );

        $traceIds = [];

        $lastId = null;

        foreach ($cursor as $item) {
            $traceIds[] = $item['traceId'];

            $lastId = (string) $item['_id'];
        }

        return new TraceTreeCachePageDto(
            traceIds: $traceIds,
            lastId: $lastId
        );
    }

    public function existsByRootTraceId(string $rootTraceId): bool
    {
        return TraceTreeCache::sconcur()->findOne(
            filter: ['rootTraceId' => $rootTraceId],
            projection: ['_id' => 1]
        ) !== null;
    }

    /**
     * @return iterable<int, string[]>
     */
    public function findTraceIds(string $rootTraceId, int $batchCount): iterable
    {
        if ($batchCount <= 0) {
            throw new InvalidArgumentException('Batch count must be greater than 0');
        }

        $cursor = TraceTreeCache::sconcur()
            ->find(
                filter: [
                    'rootTraceId' => $rootTraceId,
                    'depth'       => ['$gte' => 0],
                ],
                projection: [
                    '_id'     => 0,
                    'traceId' => 1,
                ],
                batchSize: $batchCount
            );

        $traceIds = [];

        foreach ($cursor as $item) {
            $traceIds[] = $item['traceId'];

            if (count($traceIds) < $batchCount) {
                continue;
            }

            yield $traceIds;

            $traceIds = [];
        }

        if ($traceIds !== []) {
            yield $traceIds;
        }
    }

    public function findMany(string $rootTraceId): TraceTreeRawIterator
    {
        $cursor = TraceTreeCache::sconcur()
            ->aggregate(
                [
                    [
                        '$match' => [
                            'rootTraceId' => $rootTraceId,
                        ],
                    ],
                ]
            );

        return new TraceTreeRawIterator(
            transport: static fn(array $item): TraceTreeRawObject => new TraceTreeRawObject(
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

    /**
     * @return TraceTreeServiceDto[]
     */
    public function findServices(string $rootTraceId): array
    {
        $results = TraceTreeCache::sconcur()
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
                id: (int) $result['_id'],
                tracesCount: (int) $result['count'],
            );
        }

        return $services;
    }

    /**
     * @return TraceTreeStringableObject[]
     */
    public function findTypes(string $rootTraceId): array
    {
        $results = TraceTreeCache::sconcur()
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
                name: $result['_id'],
                tracesCount: (int) $result['count'],
            );
        }

        return $types;
    }

    /**
     * @return TraceTreeStringableObject[]
     */
    public function findTags(string $rootTraceId): array
    {
        $results = TraceTreeCache::sconcur()
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
                name: $result['_id'],
                tracesCount: (int) $result['count'],
            );
        }

        return $tags;
    }

    /**
     * @return TraceTreeStringableObject[]
     */
    public function findStatuses(string $rootTraceId): array
    {
        $results = TraceTreeCache::sconcur()
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
                name: $result['_id'],
                tracesCount: (int) $result['count'],
            );
        }

        return $statuses;
    }

    public function findCount(string $rootTraceId): int
    {
        return TraceTreeCache::sconcur()
            ->countDocuments([
                'rootTraceId' => $rootTraceId,
            ]);
    }
}
