<?php

namespace App\Modules\Trace\Framework\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Common\Infrastructure\Http\Resources\PaginatorInfoResource;
use App\Modules\Trace\Domain\Entities\Objects\TraceItemObjects;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceItemsResource extends AbstractApiResource
{
    #[OaListItemTypeAttribute(TraceItemResource::class)]
    private array $items;
    private PaginatorInfoResource $paginator;

    public function __construct(TraceItemObjects $resource)
    {
        parent::__construct($resource);

        $this->items     = TraceItemResource::mapIntoMe($resource->items);
        $this->paginator = new PaginatorInfoResource($resource->paginationInfo);
    }
}
