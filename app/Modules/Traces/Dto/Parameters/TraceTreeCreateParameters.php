<?php

namespace App\Modules\Traces\Dto\Parameters;

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
