<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects\Profiling;

readonly class ProfilingItemDataObject
{
    public function __construct(
        public string $name,
        public int|float $value,
    ) {
    }
}
