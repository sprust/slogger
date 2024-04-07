<?php

namespace App\Modules\TraceCollector\Domain\Entities\Parameters;

use Illuminate\Support\Carbon;

class TraceTreeCreateParameters
{
    public function __construct(
        public string $traceId,
        public ?string $parentTraceId,
        public Carbon $loggedAt
    ) {
    }
}
