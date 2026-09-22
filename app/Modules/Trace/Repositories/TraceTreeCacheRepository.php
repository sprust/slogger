<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceTreeCache;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawIterator;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeStringableObject;
use App\Modules\Trace\Parameters\CreateTraceTreeCacheParameters;
use App\Modules\Trace\Parameters\TraceTreeFilterParameters;
use App\Modules\Trace\Repositories\Dto\Trace\Tree\TraceTreeCachePageDto;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeChildrenCursorObject;
use App\Modules\Trace\Repositories\Dto\Trace\Tree\TraceTreeChildrenPageDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceTreeServiceDto;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use SConcur\Bson\ObjectId;
use SConcur\Bson\UTCDateTime;

class TraceTreeCacheRepository
{
    /**
     * How many nodes a whole-tree read takes from the server per round trip.
     *
     * The default leaves a tree of a million nodes to thousands of getMore calls; ten
     * thousand projected nodes are a few megabytes, which is nothing to hold at once.
     */
    private const int DUMP_BATCH_SIZE = 10000;

    /**
     * What a node of the tree is made of, as the panel reads it.
     */
    private const array NODE_PROJECTION = [
        'serviceId'     => 1,
        'traceId'       => 1,
        'parentTraceId' => 1,
        'type'          => 1,
        'tags'          => 1,
        'status'        => 1,
        'duration'      => 1,
        'memory'        => 1,
        'cpu'           => 1,
        'loggedAt'      => 1,
    ];

    /**
     * The depth the chain above a root is written at, and the root's own.
     */
    private const int ANCESTOR_DEPTH = -1;

    private const int ROOT_DEPTH = 0;

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
    public function createMany(string $rootTraceId, int $depth, array $parametersList): int
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

        return TraceTreeCache::sconcur()->bulkWrite($operations)->upsertedCount;
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
                pipeline: [
                    [
                        '$match' => [
                            'rootTraceId' => $rootTraceId,
                        ],
                    ],
                    [
                        '$project' => ['_id' => 0] + self::NODE_PROJECTION,
                    ],
                ],
                batchSize: self::DUMP_BATCH_SIZE
            );

        return new TraceTreeRawIterator(
            transport: static fn(array $item): TraceTreeRawObject => self::makeRawObject($item),
            iterator: $cursor
        );
    }

    /**
     * The top of a tree: the highest of its nodes the cache holds.
     *
     * A tree opened from a trace nested in another keeps the chain above that trace at the
     * ancestor depth, and its top is the highest link of that chain; otherwise the top is
     * the root itself. Either way it is the node whose parent the cache does not have.
     *
     * @return TraceTreeRawObject[]
     */
    public function findTopNodes(string $rootTraceId): array
    {
        $cursor = TraceTreeCache::sconcur()
            ->find(
                filter: [
                    'rootTraceId' => $rootTraceId,
                    'depth'       => ['$gte' => self::ANCESTOR_DEPTH, '$lte' => self::ROOT_DEPTH],
                ],
                projection: ['_id' => 0] + self::NODE_PROJECTION
            );

        /** @var array<string, array<string, mixed>> $items */
        $items = [];

        foreach ($cursor as $item) {
            /** @var array<string, mixed> $item */
            $items[(string) $item['traceId']] = $item;
        }

        $top = [];

        foreach ($items as $item) {
            $parentTraceId = $item['parentTraceId'];

            if ($parentTraceId === null || !isset($items[$parentTraceId])) {
                $top[] = self::makeRawObject($item);
            }
        }

        return $top;
    }

    /**
     * One page of a node's children, in the order the panel shows them.
     *
     * The cursor is where the previous page stopped: the loggedAt of its last node in
     * milliseconds and that node's _id, since two children can share a millisecond.
     */
    public function findChildrenPage(
        string $rootTraceId,
        string $parentTraceId,
        ?TraceTreeChildrenCursorObject $after,
        int $limit
    ): TraceTreeChildrenPageDto {
        if ($limit <= 0) {
            throw new InvalidArgumentException('Limit must be greater than 0');
        }

        $filter = [
            'rootTraceId'   => $rootTraceId,
            'parentTraceId' => $parentTraceId,
        ];

        if ($after !== null) {
            $loggedAt = new UTCDateTime($after->loggedAtMs);

            $filter['$or'] = [
                ['loggedAt' => ['$gt' => $loggedAt]],
                ['loggedAt' => $loggedAt, '_id' => ['$gt' => new ObjectId($after->id)]],
            ];
        }

        // One more than asked for: whether it comes back is whether there is a next page.
        $cursor = TraceTreeCache::sconcur()
            ->find(
                filter: $filter,
                projection: ['_id' => 1] + self::NODE_PROJECTION,
                sort: ['loggedAt' => 1, '_id' => 1],
                limit: $limit + 1,
                batchSize: $limit + 1
            );

        $items = [];

        $next = null;

        foreach ($cursor as $item) {
            if (count($items) === $limit) {
                $last = $items[$limit - 1];

                $next = new TraceTreeChildrenCursorObject(
                    loggedAtMs: $last['loggedAt']->epochMs,
                    id: (string) $last['_id'],
                );

                break;
            }

            $items[] = $item;
        }

        return new TraceTreeChildrenPageDto(
            items: array_map(static fn(array $item): TraceTreeRawObject => self::makeRawObject($item), $items),
            next: $next,
        );
    }

    /**
     * How many children each of these nodes has in the tree — what tells the panel which
     * rows it can open.
     *
     * @param string[] $parentTraceIds
     *
     * @return array<string, int>
     */
    public function countChildren(string $rootTraceId, array $parentTraceIds): array
    {
        if ($parentTraceIds === []) {
            return [];
        }

        $cursor = TraceTreeCache::sconcur()
            ->aggregate([
                [
                    '$match' => [
                        'rootTraceId'   => $rootTraceId,
                        'parentTraceId' => ['$in' => $parentTraceIds],
                    ],
                ],
                [
                    '$group' => [
                        '_id'   => '$parentTraceId',
                        'count' => ['$sum' => 1],
                    ],
                ],
            ]);

        $counts = [];

        foreach ($cursor as $row) {
            $counts[(string) $row['_id']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * The nodes of a tree that match a filter, shallowest first: the order of the
     * (rootTraceId, depth, _id) index, so the read stops at the limit without a sort.
     *
     * @return TraceTreeRawObject[]
     */
    public function findFiltered(string $rootTraceId, TraceTreeFilterParameters $parameters, int $limit): array
    {
        if ($limit <= 0) {
            throw new InvalidArgumentException('Limit must be greater than 0');
        }

        $filter = [
            'rootTraceId' => $rootTraceId,
        ];

        if ($parameters->serviceIds !== []) {
            $filter['serviceId'] = ['$in' => $parameters->serviceIds];
        }

        if ($parameters->types !== []) {
            $filter['type'] = ['$in' => $parameters->types];
        }

        if ($parameters->tags !== []) {
            $filter['tags'] = ['$in' => $parameters->tags];
        }

        if ($parameters->statuses !== []) {
            $filter['status'] = ['$in' => $parameters->statuses];
        }

        $cursor = TraceTreeCache::sconcur()
            ->find(
                filter: $filter,
                projection: ['_id' => 0] + self::NODE_PROJECTION,
                sort: ['depth' => 1, '_id' => 1],
                limit: $limit,
                batchSize: min($limit, self::DUMP_BATCH_SIZE),
                hint: ['rootTraceId' => 1, 'depth' => 1, '_id' => 1]
            );

        $items = [];

        foreach ($cursor as $item) {
            /** @var array<string, mixed> $item */
            $items[] = self::makeRawObject($item);
        }

        return $items;
    }

    /**
     * @param string[] $traceIds
     *
     * @return TraceTreeRawObject[]
     */
    public function findByTraceIds(string $rootTraceId, array $traceIds): array
    {
        if ($traceIds === []) {
            return [];
        }

        $cursor = TraceTreeCache::sconcur()
            ->find(
                filter: [
                    'rootTraceId' => $rootTraceId,
                    'traceId'     => ['$in' => $traceIds],
                ],
                projection: ['_id' => 0] + self::NODE_PROJECTION,
                batchSize: min(count($traceIds), self::DUMP_BATCH_SIZE)
            );

        $items = [];

        foreach ($cursor as $item) {
            /** @var array<string, mixed> $item */
            $items[] = self::makeRawObject($item);
        }

        return $items;
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

    /**
     * @param array<string, mixed> $item
     */
    private static function makeRawObject(array $item): TraceTreeRawObject
    {
        /** @var UTCDateTime $loggedAt */
        $loggedAt = $item['loggedAt'];

        return new TraceTreeRawObject(
            serviceId: $item['serviceId'],
            traceId: $item['traceId'],
            parentTraceId: $item['parentTraceId'],
            type: $item['type'],
            tags: (array) $item['tags'],
            status: $item['status'],
            duration: $item['duration'],
            memory: $item['memory'],
            cpu: $item['cpu'],
            loggedAt: new Carbon($loggedAt->toDateTime()),
        );
    }
}
