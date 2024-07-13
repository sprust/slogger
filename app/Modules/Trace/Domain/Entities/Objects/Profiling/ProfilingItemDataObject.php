<?php

namespace App\Modules\Trace\Domain\Entities\Objects\Profiling;

class ProfilingItemDataObject
{
    public function __construct(
        public string $name,
        public int|float $value,
        public float $weightPercent
    ) {
    }
}
