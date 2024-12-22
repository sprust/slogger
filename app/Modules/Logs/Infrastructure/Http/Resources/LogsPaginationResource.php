<?php

namespace App\Modules\Logs\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Common\Infrastructure\Http\Resources\PaginatorInfoResource;
use App\Modules\Logs\Entities\Log\LogsPaginationObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class LogsPaginationResource extends AbstractApiResource
{
    #[OaListItemTypeAttribute(LogResource::class)]
    private array $items;
    private PaginatorInfoResource $paginator;

    public function __construct(LogsPaginationObject $resource)
    {
        parent::__construct($resource);

        $this->items     = LogResource::mapIntoMe($resource->items);
        $this->paginator = new PaginatorInfoResource($resource->paginationInfo);
    }
}
