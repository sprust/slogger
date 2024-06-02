<?php

namespace App\Modules\TraceCollector\Repositories\Dto;

use Illuminate\Support\Carbon;

class TraceTreeCreateParametersDto
{
    public function __construct(
        public string $traceId,
        public ?string $parentTraceId,
        public Carbon $loggedAt
    ) {
    }
}
