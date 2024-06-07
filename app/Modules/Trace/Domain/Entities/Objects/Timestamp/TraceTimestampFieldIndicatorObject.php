<?php

namespace App\Modules\Trace\Domain\Entities\Objects\Timestamp;

class TraceTimestampFieldIndicatorObject
{
    public function __construct(
        public string $name,
        public int|float $value
    ) {
    }
}
