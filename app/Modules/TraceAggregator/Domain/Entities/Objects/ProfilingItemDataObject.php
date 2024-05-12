<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

readonly class ProfilingItemDataObject
{
    public function __construct(
        public string $name,
        public int|float $value,
    ) {
    }
}
