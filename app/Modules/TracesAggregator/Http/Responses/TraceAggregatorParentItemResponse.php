<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TracesAggregator\Dto\Objects\TraceParentObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceAggregatorParentItemResponse extends AbstractApiResource
{
    private TraceAggregatorTraceResponse $trace;
    #[OaListItemTypeAttribute(TraceAggregatorParentItemTypeResponse::class)]
    private array $types;

    public function __construct(TraceParentObject $parentItem)
    {
        parent::__construct($parentItem);

        $this->trace = new TraceAggregatorTraceResponse($parentItem->trace);
        $this->types = TraceAggregatorParentItemTypeResponse::mapIntoMe($parentItem->types);
    }
}
