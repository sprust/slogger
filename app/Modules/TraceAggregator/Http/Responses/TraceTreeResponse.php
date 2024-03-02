<?php

namespace App\Modules\TraceAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Dto\Objects\TraceTreeObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTreeResponse extends AbstractApiResource
{
    private ?TraceServiceResponse $service;
    private string $trace_id;
    private ?string $parent_trace_id;
    private string $type;
    private string $status;
    #[OaListItemTypeAttribute('string')]
    private array $tags;
    private ?float $duration;
    private ?float $memory;
    private ?float $cpu;
    private string $logged_at;
    #[OaListItemTypeAttribute(TraceTreeResponse::class, isRecursive: true)]
    private array $children;
    private int $depth;

    public function __construct(TraceTreeObject $traceTreeNodeObject)
    {
        parent::__construct($traceTreeNodeObject);

        $this->service         = $traceTreeNodeObject->serviceObject
            ? new TraceServiceResponse(
                $traceTreeNodeObject->serviceObject
            )
            : null;
        $this->trace_id        = $traceTreeNodeObject->traceId;
        $this->parent_trace_id = $traceTreeNodeObject->parentTraceId;
        $this->type            = $traceTreeNodeObject->type;
        $this->status          = $traceTreeNodeObject->status;
        $this->tags            = $traceTreeNodeObject->tags;
        $this->duration        = $traceTreeNodeObject->duration;
        $this->memory          = $traceTreeNodeObject->memory;
        $this->cpu             = $traceTreeNodeObject->cpu;
        $this->logged_at       = $traceTreeNodeObject->loggedAt->toDateTimeString('microsecond');
        $this->children        = TraceTreeResponse::mapIntoMe($traceTreeNodeObject->children);
        $this->depth           = $traceTreeNodeObject->depth;
    }
}
