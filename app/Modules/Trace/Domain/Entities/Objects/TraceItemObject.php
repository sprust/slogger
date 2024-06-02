<?php

namespace App\Modules\Trace\Domain\Entities\Objects;

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
