<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace;

readonly class TraceItemObject
{
    public function __construct(
        public TraceItemTraceObject $trace,
    ) {
    }
}
