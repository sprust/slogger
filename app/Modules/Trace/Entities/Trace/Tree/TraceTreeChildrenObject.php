<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

/**
 * A page of a tree opened branch by branch: the nodes, how many children each of them
 * has, and where the next page starts.
 */
readonly class TraceTreeChildrenObject
{
    /**
     * @param TraceTreeRawObject[] $items
     * @param array<string, int>   $childrenCounts by trace id; a node missing here has none
     */
    public function __construct(
        public array $items,
        public array $childrenCounts,
        public ?string $nextCursor,
    ) {
    }
}
