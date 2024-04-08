<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\Common\Http\Resources\PaginatorInfoResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceItemObjects;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceItemsResource extends AbstractApiResource
{
    #[OaListItemTypeAttribute(TraceItemResource::class)]
    private array $items;
    private PaginatorInfoResource $paginator;

    public function __construct(TraceItemObjects $objects)
    {
        parent::__construct($objects);

        $this->items     = TraceItemResource::mapIntoMe($objects->items);
        $this->paginator = new PaginatorInfoResource($objects->paginationInfo);
    }
}
