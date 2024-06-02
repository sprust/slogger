<?php

namespace App\Modules\Trace\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\Common\Framework\Http\Resources\PaginatorInfoResource;
use App\Modules\Trace\Domain\Entities\Objects\TraceItemObjects;
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
