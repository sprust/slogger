<?php

namespace App\Modules\Trace\Entities\Trace;

readonly class TraceItemObject
{
    /**
     * @param TraceTypeCountedObject[] $types
     */
    public function __construct(
        public TraceItemTraceObject $trace,
        public array $types
    ) {
    }
}
