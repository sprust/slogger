<?php

namespace App\Modules\TracesAggregator\Dto\Objects;

readonly class TraceTreeObject
{
    public function __construct(
        public string $traceId,
        public ?string $parentTraceId
    ) {
    }
}
