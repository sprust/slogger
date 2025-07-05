<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

readonly class TraceTreeChildrenObjects
{
    /**
     * @param TraceTreeChildObject[] $items
     */
    public function __construct(
        public array $items,
        public bool $hasMore
    ) {
    }
}
