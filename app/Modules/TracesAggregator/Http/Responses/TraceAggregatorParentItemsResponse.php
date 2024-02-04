<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Http\Resources\PaginatorInfoResource;
use App\Modules\TracesAggregator\Dto\Objects\TraceParentObjects;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceAggregatorParentItemsResponse extends AbstractApiResource
{
    #[OaListItemTypeAttribute(TraceAggregatorParentItemResponse::class)]
    private array $items;
    private PaginatorInfoResource $paginator;

    public function __construct(TraceParentObjects $parents)
    {
        parent::__construct($parents);

        $this->items     = TraceAggregatorParentItemResponse::mapIntoMe($parents->items);
        $this->paginator = new PaginatorInfoResource($parents->paginationInfo);
    }
}
