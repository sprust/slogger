<?php

namespace App\Modules\Trace\Domain\Entities\Objects\Profiling;

readonly class ProfilingItemDataObject
{
    public function __construct(
        public string $name,
        public int|float $value,
        public float $weightPercent
    ) {
    }
}
