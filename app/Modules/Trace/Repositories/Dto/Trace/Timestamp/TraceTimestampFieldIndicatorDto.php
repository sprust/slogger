<?php

namespace App\Modules\Trace\Repositories\Dto\Trace\Timestamp;

class TraceTimestampFieldIndicatorDto
{
    public function __construct(
        public string $name,
        public int|float $value
    ) {
    }
}
