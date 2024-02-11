<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TracesAggregator\Dto\TraceObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceAggregatorTraceResponse extends AbstractApiResource
{
    private int $service_id;
    private string $trace_id;
    private ?string $parent_trace_id;
    private string $type;
    #[OaListItemTypeAttribute('string')]
    private array $tags;
    private TraceAggregatorTraceDataNodeResponse $data;
    private string $logged_at;
    private string $created_at;
    private string $updated_at;

    public function __construct(TraceObject $trace)
    {
        parent::__construct($trace);

        $this->service_id      = $trace->serviceId;
        $this->trace_id        = $trace->traceId;
        $this->parent_trace_id = $trace->parentTraceId;
        $this->type            = $trace->type;
        $this->tags            = $trace->tags;
        $this->data            = new TraceAggregatorTraceDataNodeResponse($trace->data);
        $this->logged_at       = $trace->loggedAt->toDateTimeString('microsecond');
        $this->created_at      = $trace->createdAt->toDateTimeString('microsecond');
        $this->updated_at      = $trace->updatedAt->toDateTimeString('microsecond');
    }
}
