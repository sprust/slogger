<?php

namespace App\Modules\Trace\Repositories\Dto;

class TraceTimestampFieldIndicatorDto
{
    public function __construct(
        public string $name,
        public int|float $value
    ) {
    }
}
