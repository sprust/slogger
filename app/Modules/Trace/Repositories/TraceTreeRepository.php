<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceTree;
use InvalidArgumentException;
use Iterator;
use SConcur\WaitGroup;

readonly class TraceTreeRepository
{
    private int $maxDepthForFindParent;
    private int $treeTraversalChunkSize;
    private int $treeTraversalConcurrency;

    public function __construct()
    {
        $this->maxDepthForFindParent    = 100;
        $this->treeTraversalChunkSize   = 1000;
        $this->treeTraversalConcurrency = 8;
    }

    public function findParentTraceId(string $traceId): ?string
    {
        $trace = TraceTree::sconcur()->findOne(
            filter: ['tid' => $traceId],
            projection: ['tid' => 1, 'ptid' => 1],
        );

        if (!$trace) {
            return null;
        }

        $parentTrace = $trace;

        if ($trace['ptid'] ?? null) {
            $index = 0;

            while (++$index <= $this->maxDepthForFindParent) {
                if (!($parentTrace['ptid'] ?? null)) {
                    break;
                }

                $currentParentTrace = TraceTree::sconcur()->findOne(
                    filter: ['tid' => $parentTrace['ptid']],
                    projection: ['tid' => 1, 'ptid' => 1],
                );

                if (!$currentParentTrace) {
                    break;
                }

                $parentTrace = $currentParentTrace;
            }
        }

        return $parentTrace['tid'];
    }

    /**
     * @return string[]
     */
    public function findChainToParentTraceId(string $traceId): array
    {
        $trace = TraceTree::sconcur()->findOne(
            filter: ['tid' => $traceId],
            projection: ['tid' => 1, 'ptid' => 1],
        );

        if (!$trace) {
            return [];
        }

        $chain = [];

        $parentTrace = $trace;

        if ($trace['ptid'] ?? null) {
            $index = 0;

            while (++$index <= $this->maxDepthForFindParent) {
                if (!($parentTrace['ptid'] ?? null)) {
                    break;
                }

                $currentParentTrace = TraceTree::sconcur()->findOne(
                    filter: ['tid' => $parentTrace['ptid']],
                    projection: ['tid' => 1, 'ptid' => 1],
                );

                if (!$currentParentTrace) {
                    break;
                }

                $parentTrace = $currentParentTrace;

                $chain[] = $parentTrace['tid'];
            }
        }

        return $chain;
    }

    /**
     * @return iterable<int, string[]>
     */
    public function findTraceIdsInTreeByParentTraceId(string $traceId, int $batchCount): iterable
    {
        if ($batchCount <= 0) {
            throw new InvalidArgumentException('Batch count must be greater than 0');
        }

        $frontier = [
            $traceId,
        ];

        while (count($frontier) > 0) {
            $children = [];
            $yielded  = 0;

            foreach ($this->makeFrontierGroups($frontier) as $frontierGroup) {
                $waitGroup = WaitGroup::create();

                foreach ($frontierGroup as $frontierChunk) {
                    $waitGroup->add(
                        function () use ($frontierChunk, &$children) {
                            foreach ($this->findDirectChildrenTraceIds($frontierChunk) as $childTraceId) {
                                $children[] = $childTraceId;
                            }
                        }
                    );
                }

                $waitGroup->waitAll();

                while ((count($children) - $yielded) >= $batchCount) {
                    yield array_slice($children, $yielded, $batchCount);

                    $yielded += $batchCount;
                }
            }

            if ($yielded < count($children)) {
                yield array_slice($children, $yielded);
            }

            $frontier = $children;
        }
    }

    /**
     * @param string[] $parentTraceIds
     *
     * @return iterable<int, string[]>
     */
    public function findChildrenTraceIds(array $parentTraceIds, int $batchCount): iterable
    {
        if ($batchCount <= 0) {
            throw new InvalidArgumentException('Batch count must be greater than 0');
        }

        if ($parentTraceIds === []) {
            return;
        }

        /** @var Iterator<array{tid: string}> $childrenCursor */
        $childrenCursor = TraceTree::sconcur()
            ->aggregate(
                pipeline: [
                    [
                        '$match' => [
                            'ptid' => [
                                '$in' => $parentTraceIds,
                            ],
                        ],
                    ],
                    [
                        '$project' => [
                            '_id' => 0,
                            'tid' => 1,
                        ],
                    ],
                ],
                batchSize: $batchCount
            );

        $childIds = [];

        foreach ($childrenCursor as $item) {
            $childIds[] = $item['tid'];

            if (count($childIds) < $batchCount) {
                continue;
            }

            yield $childIds;

            $childIds = [];
        }

        if ($childIds !== []) {
            yield $childIds;
        }
    }

    /**
     * @param string[] $frontier
     *
     * @return iterable<int, string[][]>
     */
    private function makeFrontierGroups(array $frontier): iterable
    {
        $total = count($frontier);

        $groupSize = $this->treeTraversalChunkSize * $this->treeTraversalConcurrency;

        for ($offset = 0; $offset < $total; $offset += $groupSize) {
            $group = [];

            for ($index = 0; $index < $this->treeTraversalConcurrency; $index++) {
                $chunk = array_slice(
                    $frontier,
                    $offset + $index * $this->treeTraversalChunkSize,
                    $this->treeTraversalChunkSize
                );

                if ($chunk === []) {
                    break;
                }

                $group[] = $chunk;
            }

            yield $group;
        }
    }

    /**
     * @param string[] $parentTraceIds
     *
     * @return string[]
     */
    private function findDirectChildrenTraceIds(array $parentTraceIds): array
    {
        $childIds = [];

        foreach ($this->findChildrenTraceIds($parentTraceIds, $this->treeTraversalChunkSize) as $childIdsChunk) {
            foreach ($childIdsChunk as $childTraceId) {
                $childIds[] = $childTraceId;
            }
        }

        return $childIds;
    }
}
