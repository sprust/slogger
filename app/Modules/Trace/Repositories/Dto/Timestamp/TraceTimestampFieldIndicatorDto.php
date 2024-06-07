<?php

namespace App\Modules\Trace\Repositories\Dto\Timestamp;

class TraceTimestampFieldIndicatorDto
{
    public function __construct(
        public string $name,
        public int|float $value
    ) {
    }
}
