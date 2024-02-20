<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TracesAggregator\Dto\Objects\TraceTreeNodeObjects;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceAggregatorTreeNodesResponse extends AbstractApiResource
{
    #[OaListItemTypeAttribute(TraceAggregatorTreeNodeResponse::class, isRecursive: true)]
    private array $items;

    public function __construct(TraceTreeNodeObjects $traceTreeNodeObjects)
    {
        parent::__construct($traceTreeNodeObjects);

        $this->items = TraceAggregatorTreeNodeResponse::mapIntoMe($traceTreeNodeObjects->items);
    }
}
