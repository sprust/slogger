<?php

namespace App\Modules\TracesAggregator\Dto\Parameters;

readonly class TraceFindTreeParameters
{
    public function __construct(
        public string $traceId
    ) {
    }
}
