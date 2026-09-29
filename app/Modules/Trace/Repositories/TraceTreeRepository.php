<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Services\Clickhouse\ClickhouseClient;
use App\Services\Clickhouse\ClickhouseQueryException;
use InvalidArgumentException;
use SConcur\WaitGroup;

readonly class TraceTreeRepository
{
    private int $maxDepthForFindParent;
    private int $treeTraversalChunkSize;
    private int $treeTraversalConcurrency;

    public function __construct(
        private ClickhouseClient $client,
    ) {
        $this->maxDepthForFindParent    = 100;
        $this->treeTraversalChunkSize   = 1000;
        $this->treeTraversalConcurrency = 8;
    }

    public function findParentTraceId(string $traceId): ?string
    {
        $trace = $this->findLink($traceId);

        if (!$trace) {
            return null;
        }

        $parentTrace = $trace;

        if ($trace['ptid'] !== '') {
            $index = 0;

            while (++$index <= $this->maxDepthForFindParent) {
                if ($parentTrace['ptid'] === '') {
                    break;
                }

                $currentParentTrace = $this->findLink($parentTrace['ptid']);

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
        $trace = $this->findLink($traceId);

        if (!$trace) {
            return [];
        }

        $chain = [];

        $parentTrace = $trace;

        if ($trace['ptid'] !== '') {
            $index = 0;

            while (++$index <= $this->maxDepthForFindParent) {
                if ($parentTrace['ptid'] === '') {
                    break;
                }

                $currentParentTrace = $this->findLink($parentTrace['ptid']);

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

        $rows = $this->client->select(
            sql: 'SELECT DISTINCT tid FROM traces WHERE ptid IN {parents:Array(String)}',
            params: ['parents' => array_values($parentTraceIds)],
            queryIdPrefix: 'trace-tree-children'
        );

        $childIds = [];

        foreach ($rows as $row) {
            $childIds[] = (string) $row['tid'];

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
     * A trace and its parent, from the latest version of the trace.
     *
     * @return array{tid: string, ptid: string}|null
     *
     * @throws ClickhouseQueryException
     */
    private function findLink(string $traceId): ?array
    {
        $rows = $this->client->select(
            sql: 'SELECT tid, ptid FROM traces WHERE tid = {tid:String} ORDER BY uat DESC LIMIT 1',
            params: ['tid' => $traceId],
            queryIdPrefix: 'trace-tree-link'
        );

        if (!isset($rows[0])) {
            return null;
        }

        return [
            'tid'  => (string) $rows[0]['tid'],
            'ptid' => (string) $rows[0]['ptid'],
        ];
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
