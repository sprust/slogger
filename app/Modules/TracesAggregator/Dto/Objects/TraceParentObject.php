<?php

namespace App\Modules\TracesAggregator\Dto\Objects;

use App\Modules\TracesAggregator\Dto\TraceObject;

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
