<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampsObjects;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTimestampsResource extends AbstractApiResource
{
    #[OaListItemTypeAttribute(TraceTimestampResource::class)]
    private array $items;

    public function __construct(TraceTimestampsObjects $resource)
    {
        parent::__construct($resource);

        $this->items = TraceTimestampResource::mapIntoMe($resource->items);
    }
}
