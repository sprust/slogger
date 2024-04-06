<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

readonly class TraceItemObject
{
    /**
     * @param TraceItemTypeObject[] $types
     */
    public function __construct(
        public TraceItemTraceObject $trace,
        public array $types
    ) {
    }
}
