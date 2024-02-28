<?php

namespace App\Modules\TracesAggregator\Dto\Objects;

class TraceTreeNodeObjects
{
    /**
     * @param TraceTreeNodeObject[] $items
     */
    public function __construct(
        public array $items
    ) {
    }
}
