<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeChildrenObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeChildrenCursorObject;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;

/**
 * A tree too large to send whole, one branch at a time.
 *
 * With no parent it answers with the top of the tree; with one, with a page of that node's
 * children. Every node comes with its number of children, so the panel knows which rows
 * can be opened without asking for them first.
 */
readonly class FindTraceTreeChildrenAction
{
    public function __construct(
        private TraceTreeCacheRepository $traceTreeCacheRepository,
    ) {
    }

    public function handle(string $rootTraceId, ?string $parentTraceId, ?string $cursor, int $limit): TraceTreeChildrenObject
    {
        if ($parentTraceId === null) {
            $items = $this->traceTreeCacheRepository->findTopNodes($rootTraceId);

            $nextCursor = null;
        } else {
            $page = $this->traceTreeCacheRepository->findChildrenPage(
                rootTraceId: $rootTraceId,
                parentTraceId: $parentTraceId,
                after: $cursor === null ? null : TraceTreeChildrenCursorObject::fromString($cursor),
                limit: $limit
            );

            $items = $page->items;

            $nextCursor = $page->next?->toString();
        }

        return new TraceTreeChildrenObject(
            items: $items,
            childrenCounts: $this->traceTreeCacheRepository->countChildren(
                rootTraceId: $rootTraceId,
                parentTraceIds: array_map(
                    static fn(TraceTreeRawObject $item): string => $item->traceId,
                    $items
                )
            ),
            nextCursor: $nextCursor,
        );
    }
}
