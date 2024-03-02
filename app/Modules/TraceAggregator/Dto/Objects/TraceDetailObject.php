<?php

namespace App\Modules\TraceAggregator\Dto\Objects;

use App\Models\Traces\Trace;
use App\Modules\TraceAggregator\Services\TraceDataToObjectConverter;
use Carbon\Carbon;

readonly class TraceDetailObject
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        public ?TraceServiceObject $service,
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public string $status,
        public array $tags,
        public TraceDataObject $data,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public Carbon $loggedAt,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }

    public static function fromModel(Trace $trace): static
    {
        return new static(
            service: $trace->service
                ? new TraceServiceObject(
                    id: $trace->service->id,
                    name: $trace->service->name,
                )
                : null,
            traceId: $trace->traceId,
            parentTraceId: $trace->parentTraceId,
            type: $trace->type,
            status: $trace->status,
            tags: $trace->tags,
            data: (new TraceDataToObjectConverter($trace->data))->convert(),
            duration: $trace->duration,
            memory: $trace->memory,
            cpu: $trace->cpu,
            loggedAt: $trace->loggedAt,
            createdAt: $trace->createdAt,
            updatedAt: $trace->updatedAt
        );
    }
}
