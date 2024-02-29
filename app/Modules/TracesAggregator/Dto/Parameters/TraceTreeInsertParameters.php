<?php

namespace App\Modules\TracesAggregator\Dto\Parameters;

readonly class TraceTreeInsertParameters
{
    public function __construct(
        public string $traceId,
        public ?string $parentTraceId
    ) {
    }
}
