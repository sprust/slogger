<?php

namespace App\Modules\Trace\Framework\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Common\Infrastructure\Http\Resources\PaginatorInfoResource;
use App\Modules\Trace\Domain\Entities\Objects\TraceAdminStoresPaginationObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceAdminStoresResource extends AbstractApiResource
{
    #[OaListItemTypeAttribute(TraceAdminStoreResource::class)]
    private array $items;
    private PaginatorInfoResource $paginator;

    public function __construct(TraceAdminStoresPaginationObject $resource)
    {
        parent::__construct($resource);

        $this->items     = TraceAdminStoreResource::mapIntoMe($resource->items);
        $this->paginator = new PaginatorInfoResource($resource->paginationInfo);
    }
}
