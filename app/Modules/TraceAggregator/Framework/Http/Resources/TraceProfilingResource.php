<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\ProfilingItemObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceProfilingResource extends AbstractApiResource
{
    private string $id;
    private string $call;
    #[OaListItemTypeAttribute(TraceProfilingDataResource::class)]
    private array $data;
    #[OaListItemTypeAttribute(TraceProfilingResource::class, isRecursive: true)]
    private array $callables;
    private ?string $link;

    public function __construct(ProfilingItemObject $resource)
    {
        parent::__construct($resource);

        $this->id        = $resource->id;
        $this->call      = $resource->call;
        $this->data      = TraceProfilingDataResource::mapIntoMe($resource->data);
        $this->callables = TraceProfilingResource::mapIntoMe($resource->callables);
        $this->link      = $resource->link;
    }
}
