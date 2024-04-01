<?php

namespace App\Modules\TraceAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\Common\Pagination\PaginationResource;
use App\Modules\TraceAggregator\Dto\Objects\ServiceStatsPaginationObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class ServiceStatsPaginationResource extends AbstractApiResource
{
    #[OaListItemTypeAttribute(ServiceStatResponse::class)]
    private array $items;
    private PaginationResource $pagination_info;

    public function __construct(ServiceStatsPaginationObject $resource)
    {
        parent::__construct($resource);

        $this->items           = ServiceStatResponse::mapIntoMe($resource->items);
        $this->pagination_info = new PaginationResource($resource->paginationInfo);
    }
}
