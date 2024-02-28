<?php

namespace App\Modules\TracesAggregator\Dto\Parameters;

readonly class TraceMapFindParameters
{
    public function __construct(
        public string $traceId
    ) {
    }
}
