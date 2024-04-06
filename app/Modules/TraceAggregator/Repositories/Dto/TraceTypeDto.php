<?php

namespace App\Modules\TraceAggregator\Repositories\Dto;

class TraceTypeDto
{
    public function __construct(
        public string $traceId,
        public string $type,
        public int $count
    ) {
    }
}
