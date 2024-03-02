<?php

namespace App\Modules\TraceAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Http\Resources\PaginatorInfoResource;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemObjects;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceItemsResponse extends AbstractApiResource
{
    #[OaListItemTypeAttribute(TraceItemResponse::class)]
    private array $items;
    private PaginatorInfoResource $paginator;

    public function __construct(TraceItemObjects $objects)
    {
        parent::__construct($objects);

        $this->items     = TraceItemResponse::mapIntoMe($objects->items);
        $this->paginator = new PaginatorInfoResource($objects->paginationInfo);
    }
}
