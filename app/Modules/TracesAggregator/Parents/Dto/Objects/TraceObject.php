<?php

namespace App\Modules\TracesAggregator\Parents\Dto\Objects;

use App\Models\Traces\Trace;
use Carbon\Carbon;

readonly class TraceObject
{
    public function __construct(
        public string $serviceId,
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public array $tags,
        public array $data,
        public Carbon $loggedAt,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }

    public static function fromModel(Trace $trace): static
    {
        return new static(
            serviceId: $trace->serviceId,
            traceId: $trace->traceId,
            parentTraceId: $trace->parentTraceId,
            type: $trace->type,
            tags: $trace->tags,
            data: $trace->data,
            loggedAt: $trace->loggedAt,
            createdAt: $trace->createdAt,
            updatedAt: $trace->updatedAt
        );
    }
}
