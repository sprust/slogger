<?php

namespace App\Modules\TraceCollector\Repositories\Dto;

use Illuminate\Support\Carbon;

readonly class TraceTreeDto
{
    public function __construct(
        public string $traceId,
        public ?string $parentTraceId,
        public Carbon $loggedAt
    ) {
    }
}
