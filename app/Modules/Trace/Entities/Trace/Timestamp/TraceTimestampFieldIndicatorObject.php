<?php

namespace App\Modules\Trace\Entities\Trace\Timestamp;

class TraceTimestampFieldIndicatorObject
{
    public function __construct(
        public string $name,
        public int|float $value
    ) {
    }
}
