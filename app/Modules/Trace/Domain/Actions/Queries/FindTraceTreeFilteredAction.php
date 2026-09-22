<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeFilteredObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject;
use App\Modules\Trace\Parameters\TraceTreeFilterParameters;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;

/**
 * A filter over the whole of a tree too large to send whole: the matching nodes and the
 * path from each up to the top, read level by level since the cache keeps only parents.
 */
readonly class FindTraceTreeFilteredAction
{
    public function __construct(
        private TraceTreeCacheRepository $traceTreeCacheRepository,
    ) {
    }

    public function handle(string $rootTraceId, TraceTreeFilterParameters $parameters): TraceTreeFilteredObject
    {
        $limit = (int) config('module-trace.tree.filter_limit');

        // One more than the limit: whether it comes back is whether the list is cut.
        $matched = $this->traceTreeCacheRepository->findFiltered(
            rootTraceId: $rootTraceId,
            parameters: $parameters,
            limit: $limit + 1
        );

        $truncated = count($matched) > $limit;

        if ($truncated) {
            $matched = array_slice($matched, 0, $limit);
        }

        /** @var array<string, TraceTreeRawObject> $nodes */
        $nodes = [];

        foreach ($matched as $node) {
            $nodes[$node->traceId] = $node;
        }

        $level = $matched;

        while ($level !== []) {
            $parentTraceIds = [];

            foreach ($level as $node) {
                $parentTraceId = $node->parentTraceId;

                if ($parentTraceId !== null && !isset($nodes[$parentTraceId])) {
                    $parentTraceIds[$parentTraceId] = true;
                }
            }

            // A parent the cache does not have is above the top of the tree.
            $level = $this->traceTreeCacheRepository->findByTraceIds(
                rootTraceId: $rootTraceId,
                traceIds: array_keys($parentTraceIds)
            );

            foreach ($level as $node) {
                $nodes[$node->traceId] = $node;
            }
        }

        return new TraceTreeFilteredObject(
            items: array_values($nodes),
            matchedCount: count($matched),
            truncated: $truncated,
        );
    }
}
