<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TracesAggregator\Dto\Objects\TraceTreeNodeObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceAggregatorTreeNodeResponse extends AbstractApiResource
{
    private ?TraceAggregatorTraceServiceResponse $service;
    private string $traceId;
    private ?string $parentTraceId;
    private string $type;
    #[OaListItemTypeAttribute('string')]
    private array $tags;
    private ?float $duration;
    private ?float $memory;
    private ?float $cpu;
    private string $loggedAt;
    #[OaListItemTypeAttribute(TraceAggregatorTreeNodeResponse::class, isRecursive: true)]
    private array $children;
    private int $depth;

    public function __construct(TraceTreeNodeObject $traceTreeNodeObject)
    {
        parent::__construct($traceTreeNodeObject);

        $this->service       = $traceTreeNodeObject->serviceObject
            ? new TraceAggregatorTraceServiceResponse(
                $traceTreeNodeObject->serviceObject
            )
            : null;
        $this->traceId       = $traceTreeNodeObject->traceId;
        $this->parentTraceId = $traceTreeNodeObject->parentTraceId;
        $this->type          = $traceTreeNodeObject->type;
        $this->tags          = $traceTreeNodeObject->tags;
        $this->duration      = $traceTreeNodeObject->duration;
        $this->memory        = $traceTreeNodeObject->memory;
        $this->cpu           = $traceTreeNodeObject->cpu;
        $this->loggedAt      = $traceTreeNodeObject->loggedAt->toDateTimeString('microsecond');
        $this->children      = TraceAggregatorTreeNodeResponse::mapIntoMe($traceTreeNodeObject->children);
        $this->depth         = $traceTreeNodeObject->depth;
    }
}
