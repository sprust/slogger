<?php

namespace App\Modules\Traces\Dto\Parameters;

class TraceTreeCreateParameters
{
    public function __construct(
        public string $traceId,
        public ?string $parentTraceId,
    ) {
    }
}
