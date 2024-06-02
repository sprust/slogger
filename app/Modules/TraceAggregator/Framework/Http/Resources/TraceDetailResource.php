<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceDetailObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceDetailResource extends AbstractApiResource
{
    private ?TraceServiceResource $service;
    private string $trace_id;
    private ?string $parent_trace_id;
    private string $type;
    private string $status;
    #[OaListItemTypeAttribute('string')]
    private array $tags;
    private TraceDataResource $data;
    private ?float $duration;
    private ?float $memory;
    private ?float $cpu;
    #[OaListItemTypeAttribute(TraceDataAdditionalFieldResource::class)]
    private string $logged_at;
    private string $created_at;
    private string $updated_at;

    public function __construct(TraceDetailObject $trace)
    {
        parent::__construct($trace);

        $this->service         = TraceServiceResource::makeIfNotNull($trace->service);
        $this->trace_id        = $trace->traceId;
        $this->parent_trace_id = $trace->parentTraceId;
        $this->type            = $trace->type;
        $this->status          = $trace->status;
        $this->tags            = $trace->tags;
        $this->data            = new TraceDataResource($trace->data);

        $this->duration = $trace->duration;
        $this->memory   = $trace->memory;
        $this->cpu      = $trace->cpu;

        $this->logged_at  = $trace->loggedAt->toDateTimeString('microsecond');
        $this->created_at = $trace->createdAt->toDateTimeString('microsecond');
        $this->updated_at = $trace->updatedAt->toDateTimeString('microsecond');
    }
}
