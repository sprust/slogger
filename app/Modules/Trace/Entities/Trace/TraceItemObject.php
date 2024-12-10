<?php

namespace App\Modules\Trace\Entities\Trace;

readonly class TraceItemObject
{
    public function __construct(
        public TraceItemTraceObject $trace,
    ) {
    }
}
