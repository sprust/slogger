<?php

namespace App\Modules\Trace\Domain\Entities\Objects\Tree;

readonly class TraceTreeObjects
{
    /**
     * @param TraceTreeObject[] $items
     */
    public function __construct(
        public int $tracesCount,
        public array $items
    ) {
    }
}
