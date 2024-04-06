<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Parameters;

readonly class TraceFindTreeParameters
{
    public function __construct(
        public string $traceId
    ) {
    }
}
