<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Parameters;

readonly class TraceFindProfilingParameters
{
    public function __construct(
        public string $traceId,
    ) {
    }
}
