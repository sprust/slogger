<?php

namespace App\Modules\Trace\Parameters\Profilling;

readonly class TraceUpdateProfilingDataObject
{
    public function __construct(
        public string $name,
        public int|float $value,
    ) {
    }
}
