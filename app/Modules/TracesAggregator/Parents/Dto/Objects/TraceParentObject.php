<?php

namespace App\Modules\TracesAggregator\Parents\Dto\Objects;

readonly class TraceParentObject
{
    /**
     * @param TraceParentTypeObject[] $types
     */
    public function __construct(
        public TraceObject $parent,
        public array $types
    ) {
    }
}
