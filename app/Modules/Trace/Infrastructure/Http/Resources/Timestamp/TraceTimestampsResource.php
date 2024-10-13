<?php

namespace App\Modules\Trace\Infrastructure\Http\Resources\Timestamp;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampsObjects;
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
