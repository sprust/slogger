<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Common\Infrastructure\Http\Resources\PaginatorInfoResource;
use App\Modules\Trace\Entities\Trace\TraceItemObjects;
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
