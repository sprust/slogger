<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampsObjects;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTimestampsResource extends AbstractApiResource
{
    private string $loggedAtFrom;
    #[OaListItemTypeAttribute(TraceTimestampResource::class)]
    private array $items;

    public function __construct(TraceTimestampsObjects $resource)
    {
        parent::__construct($resource);

        $this->loggedAtFrom = $resource->loggedAtFrom->toDateTimeString();
        $this->items        = TraceTimestampResource::mapIntoMe($resource->items);
    }
}
