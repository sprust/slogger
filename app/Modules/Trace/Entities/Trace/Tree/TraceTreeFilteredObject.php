<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

/**
 * The nodes of a tree that match a filter, with every ancestor up to the top of the tree.
 */
readonly class TraceTreeFilteredObject
{
    /**
     * @param TraceTreeRawObject[] $items
     */
    public function __construct(
        public array $items,
        public int $matchedCount,
        public bool $truncated,
    ) {
    }
}
