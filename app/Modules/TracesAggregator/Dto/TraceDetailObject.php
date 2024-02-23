<?php

namespace App\Modules\TracesAggregator\Dto;

use App\Models\Traces\Trace;
use App\Modules\TracesAggregator\Dto\Objects\TraceDataNodeObject;
use App\Modules\TracesAggregator\Services\TraceDataConverter;
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
        public array $tags,
        public TraceDataNodeObject $data,
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
            tags: $trace->tags,
            data: (new TraceDataConverter($trace->data))->convert(),
            duration: $trace->duration,
            memory: $trace->memory,
            cpu: $trace->cpu,
            loggedAt: $trace->loggedAt,
            createdAt: $trace->createdAt,
            updatedAt: $trace->updatedAt
        );
    }
}
