<?php

namespace App\Modules\TraceAggregator\Dto\Parameters;

readonly class TraceFindTreeParameters
{
    public function __construct(
        public string $traceId
    ) {
    }
}
