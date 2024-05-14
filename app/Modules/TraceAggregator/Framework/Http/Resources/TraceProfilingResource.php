<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\ProfilingObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceProfilingResource extends AbstractApiResource
{
    private string $main_caller;
    #[OaListItemTypeAttribute(TraceProfilingItemResource::class)]
    private array $items;

    public function __construct(ProfilingObject $resource)
    {
        parent::__construct($resource);

        $this->main_caller = $resource->mainCaller;
        $this->items     = TraceProfilingItemResource::mapIntoMe($resource->items);
    }
}
