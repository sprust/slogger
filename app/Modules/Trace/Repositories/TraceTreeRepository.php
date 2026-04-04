<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceTree;
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
        /** @var array{tid: string, ptid: string|null}|null $trace */
        $trace = TraceTree::query()
            ->select([
                'tid',
                'ptid',
            ])
            ->where('tid', $traceId)
            ->toBase()
            ->first();

        if (!$trace) {
            return null;
        }

        $trace = (array) $trace;

        $parentTrace = $trace;

        if ($trace['ptid']) {
            $index = 0;

            while (++$index <= $this->maxDepthForFindParent) {
                if (!$parentTrace['ptid']) {
                    break;
                }

                /** @var array{tid: string, ptid: string|null}|null $currentParentTrace */
                $currentParentTrace = TraceTree::query()
                    ->select([
                        'tid',
                        'ptid',
                    ])
                    ->where('tid', $parentTrace['ptid'])
                    ->toBase()
                    ->first();

                if (!$currentParentTrace) {
                    break;
                }

                $currentParentTrace = (array) $currentParentTrace;

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
        /** @var array{tid: string, ptid: string|null}|null $trace */
        $trace = TraceTree::query()
            ->select([
                'tid',
                'ptid',
            ])
            ->where('tid', $traceId)
            ->toBase()
            ->first();

        if (!$trace) {
            return [];
        }

        $trace = (array) $trace;

        $chain = [];

        $parentTrace = $trace;

        if ($trace['ptid']) {
            $index = 0;

            while (++$index <= $this->maxDepthForFindParent) {
                if (!$parentTrace['ptid']) {
                    break;
                }

                /** @var array{tid: string, ptid: string|null}|null $currentParentTrace */
                $currentParentTrace = TraceTree::query()
                    ->select([
                        'tid',
                        'ptid',
                    ])
                    ->where('tid', $parentTrace['ptid'])
                    ->toBase()
                    ->first();

                if (!$currentParentTrace) {
                    break;
                }

                $currentParentTrace = (array) $currentParentTrace;

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
        $visited = [
            $traceId => true,
        ];

        $frontier = [
            $traceId,
        ];

        $childIds = [];
        $depth    = 0;

        while (count($frontier) > 0 && $depth < $this->maxDepthForFindParent) {
            ++$depth;

            $nextFrontier = [];

            $waitGroup = WaitGroup::create();

            foreach (array_chunk($frontier, $this->treeTraversalChunkSize) as $frontierChunk) {
                $waitGroup->add(
                    function () use ($frontierChunk, &$visited, &$childIds, &$nextFrontier) {
                        foreach ($this->findDirectChildrenTraceIds($frontierChunk) as $childTraceId) {
                            if (isset($visited[$childTraceId])) {
                                continue;
                            }

                            $visited[$childTraceId] = true;

                            $childIds[]     = $childTraceId;
                            $nextFrontier[] = $childTraceId;
                        }
                    }
                );
            }

            $waitGroup->waitAll();

            $frontier = $nextFrontier;

            if (count($childIds) >= $batchCount) {
                yield $childIds;
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
