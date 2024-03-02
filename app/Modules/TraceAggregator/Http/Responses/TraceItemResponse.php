<?php

namespace App\Modules\TraceAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceItemResponse extends AbstractApiResource
{
    private TraceItemTraceResponse $trace;
    #[OaListItemTypeAttribute(TraceItemTypeResponse::class)]
    private array $types;

    public function __construct(TraceItemObject $parentItem)
    {
        parent::__construct($parentItem);

        $this->trace = new TraceItemTraceResponse($parentItem->trace);
        $this->types = TraceItemTypeResponse::mapIntoMe($parentItem->types);
    }
}
