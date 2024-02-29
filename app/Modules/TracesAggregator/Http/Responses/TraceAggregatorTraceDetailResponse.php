<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TracesAggregator\Dto\TraceDetailObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceAggregatorTraceDetailResponse extends AbstractApiResource
{
    private ?TraceAggregatorTraceServiceResponse $service;
    private string $trace_id;
    private ?string $parent_trace_id;
    private string $type;
    private string $status;
    #[OaListItemTypeAttribute('string')]
    private array $tags;
    private TraceAggregatorTraceDataNodeResponse $data;
    private ?float $duration;
    private ?float $memory;
    private ?float $cpu;
    #[OaListItemTypeAttribute(TraceAggregatorTraceDataAdditionalFieldResponse::class)]
    private string $logged_at;
    private string $created_at;
    private string $updated_at;

    public function __construct(TraceDetailObject $trace)
    {
        parent::__construct($trace);

        $this->service         = TraceAggregatorTraceServiceResponse::makeIfNotNull($trace->service);
        $this->trace_id        = $trace->traceId;
        $this->parent_trace_id = $trace->parentTraceId;
        $this->type            = $trace->type;
        $this->status          = $trace->status;
        $this->tags            = $trace->tags;
        $this->data            = new TraceAggregatorTraceDataNodeResponse($trace->data);

        $this->duration = $trace->duration;
        $this->memory   = $trace->memory;
        $this->cpu      = $trace->cpu;

        $this->logged_at  = $trace->loggedAt->toDateTimeString('microsecond');
        $this->created_at = $trace->createdAt->toDateTimeString('microsecond');
        $this->updated_at = $trace->updatedAt->toDateTimeString('microsecond');
    }
}
