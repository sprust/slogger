<?php

namespace App\Modules\TraceAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Dto\Objects\TraceTreeObjects;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTreesResponse extends AbstractApiResource
{
    private int $tracesCount;
    #[OaListItemTypeAttribute(TraceTreeResponse::class, isRecursive: true)]
    private array $items;

    public function __construct(TraceTreeObjects $trees)
    {
        parent::__construct($trees);

        $this->tracesCount = $trees->tracesCount;
        $this->items       = TraceTreeResponse::mapIntoMe($trees->items);
    }
}
