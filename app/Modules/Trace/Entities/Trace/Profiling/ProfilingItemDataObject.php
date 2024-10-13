<?php

namespace App\Modules\Trace\Entities\Trace\Profiling;

class ProfilingItemDataObject
{
    public function __construct(
        public string $name,
        public int|float $value,
        public float $weightPercent
    ) {
    }
}
