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

    public function __construct()
    {
        $this->maxDepthForFindParent  = 100;
        $this->treeTraversalChunkSize = 1000;
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

        $childIds = [];

        while (count($frontier) > 0) {
            $nextFrontier = [];

            $waitGroup = WaitGroup::create();

            foreach (array_chunk($frontier, $this->treeTraversalChunkSize) as $frontierChunk) {
                $waitGroup->add(
                    function () use ($frontierChunk, &$childIds, &$nextFrontier) {
                        foreach ($this->findDirectChildrenTraceIds($frontierChunk) as $childTraceId) {
                            $childIds[]     = $childTraceId;
                            $nextFrontier[] = $childTraceId;
                        }
                    }
                );
            }

            $waitGroup->waitAll();

            $frontier = $nextFrontier;

            if (count($childIds) >= $batchCount) {
                foreach (array_chunk($childIds, $batchCount) as $childIdsChunk) {
                    yield $childIdsChunk;
                }

                $childIds = [];
            }
        }

        if (count($childIds) > 0) {
            yield $childIds;
        }
    }

    /**
     * @param string[] $parentTraceIds
     *
     * @return string[]
     */
    private function findDirectChildrenTraceIds(array $parentTraceIds): array
    {
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
                            'tid' => 1,
                        ],
                    ],
                ],
                batchSize: 500
            );

        $childIds = [];

        foreach ($childrenCursor as $item) {
            $childIds[] = $item['tid'];
        }

        return $childIds;
    }
}
