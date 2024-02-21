<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TracesAggregator\Dto\Objects\TraceTreeNodeObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceAggregatorTreeNodeResponse extends AbstractApiResource
{
    private string $traceId;
    private ?string $parentTraceId;
    private string $type;
    #[OaListItemTypeAttribute('string')]
    private array $tags;
    private ?TraceAggregatorTraceServiceResponse $serviceObject;
    private string $loggedAt;
    #[OaListItemTypeAttribute(TraceAggregatorTreeNodeResponse::class, isRecursive: true)]
    private array $children;
    private int $depth;

    public function __construct(TraceTreeNodeObject $traceTreeNodeObject)
    {
        parent::__construct($traceTreeNodeObject);

        $this->traceId       = $traceTreeNodeObject->traceId;
        $this->parentTraceId = $traceTreeNodeObject->parentTraceId;
        $this->type          = $traceTreeNodeObject->type;
        $this->tags          = $traceTreeNodeObject->tags;
        $this->serviceObject = $traceTreeNodeObject->serviceObject
            ? new TraceAggregatorTraceServiceResponse(
                $traceTreeNodeObject->serviceObject
            )
            : null;
        $this->loggedAt      = $traceTreeNodeObject->loggedAt->toDateTimeString('microsecond');
        $this->children      = $traceTreeNodeObject->children;
        $this->depth         = $traceTreeNodeObject->depth;
    }
}
