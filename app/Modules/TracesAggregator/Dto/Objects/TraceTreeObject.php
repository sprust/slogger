<?php

namespace App\Modules\TracesAggregator\Dto\Objects;

use Illuminate\Support\Carbon;

readonly class TraceTreeObject
{
    public function __construct(
        public string $traceId,
        public ?string $parentTraceId,
        public Carbon $loggedAt
    ) {
    }
}
