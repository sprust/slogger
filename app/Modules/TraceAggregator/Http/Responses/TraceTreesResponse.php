<?php

namespace App\Modules\TraceAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Dto\Objects\TraceTreeObjects;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTreesResponse extends AbstractApiResource
{
    #[OaListItemTypeAttribute(TraceTreeResponse::class, isRecursive: true)]
    private array $items;

    public function __construct(TraceTreeObjects $traceTreeNodeObjects)
    {
        parent::__construct($traceTreeNodeObjects);

        $this->items = TraceTreeResponse::mapIntoMe($traceTreeNodeObjects->items);
    }
}
