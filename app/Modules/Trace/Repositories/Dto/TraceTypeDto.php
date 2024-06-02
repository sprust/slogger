<?php

namespace App\Modules\Trace\Repositories\Dto;

class TraceTypeDto
{
    public function __construct(
        public string $traceId,
        public string $type,
        public int $count
    ) {
    }
}
