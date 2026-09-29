<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Domain\Services\McpTraceTreeNodeFactory;
use App\Modules\Mcp\Entities\Bridges\McpTraceTreeObject;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeChildrenAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeStateAction;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;

readonly class FindMcpTraceTreeAction
{
    public function __construct(
        private FindTraceTreeStateAction $findTraceTreeStateAction,
        private FindTraceTreeAction $findTraceTreeAction,
        private FindTraceTreeChildrenAction $findTraceTreeChildrenAction,
        private McpTraceTreeNodeFactory $nodeFactory
    ) {
    }

    public function handle(string $traceId, ?string $parentTraceId, ?string $cursor, int $limit): ?McpTraceTreeObject
    {
        $tree = $this->findTraceTreeStateAction->handle($traceId);

        if (is_null($tree)) {
            return null;
        }

        $state = $tree->state;

        if (is_null($state)) {
            $started = $this->findTraceTreeAction->handle($traceId, fresh: false, isChild: false);

            if (is_null($started)) {
                return null;
            }

            $state = $started->state;
        }

        if ($state->status !== TraceTreeCacheStateStatusEnum::Finished) {
            return new McpTraceTreeObject(
                rootTraceId: $tree->rootTraceId,
                status: $state->status,
                error: $state->error,
                nodes: [],
                nextCursor: null,
                matchedCount: 0,
                truncated: false
            );
        }

        $children = $this->findTraceTreeChildrenAction->handle(
            rootTraceId: $tree->rootTraceId,
            parentTraceId: $parentTraceId,
            cursor: $cursor,
            limit: $limit
        );

        return new McpTraceTreeObject(
            rootTraceId: $tree->rootTraceId,
            status: $state->status,
            error: null,
            nodes: $this->nodeFactory->make($children->items, $children->childrenCounts),
            nextCursor: $children->nextCursor,
            matchedCount: count($children->items),
            truncated: false
        );
    }
}
