<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Http\Resources\PaginatorInfoResource;
use App\Modules\TracesAggregator\Dto\Objects\TraceChildObjects;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceAggregatorChildItemsResponse extends AbstractApiResource
{
    #[OaListItemTypeAttribute(TraceAggregatorChildItemResponse::class)]
    private array $items;
    private PaginatorInfoResource $paginator;

    public function __construct(TraceChildObjects $children)
    {
        parent::__construct($children);

        $this->items     = TraceAggregatorChildItemResponse::mapIntoMe($children->items);
        $this->paginator = new PaginatorInfoResource($children->paginationInfo);
    }
}
