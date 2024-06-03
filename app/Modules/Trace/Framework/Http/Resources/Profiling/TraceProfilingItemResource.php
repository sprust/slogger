<?php

namespace App\Modules\Trace\Framework\Http\Resources\Profiling;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\Profiling\ProfilingItemObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceProfilingItemResource extends AbstractApiResource
{
    private string $id;
    private string $calling;
    private string $callable;
    #[OaListItemTypeAttribute(TraceProfilingDataResource::class)]
    private array $data;

    public function __construct(ProfilingItemObject $resource)
    {
        parent::__construct($resource);

        $this->id       = $resource->id;
        $this->calling  = $resource->calling;
        $this->callable = $resource->callable;
        $this->data     = TraceProfilingDataResource::mapIntoMe($resource->data);
    }
}
