<?php

namespace App\Modules\Trace\Framework\Http\Resources\Profiling;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\Profiling\Tree\ProfilingTreeObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceProfilingTreeResource extends AbstractApiResource
{
    #[OaListItemTypeAttribute(TraceProfilingTreeNodeResource::class)]
    private array $nodes;

    public function __construct(ProfilingTreeObject $resource)
    {
        parent::__construct($resource);

        $this->nodes = TraceProfilingTreeNodeResource::mapIntoMe($resource->nodes);
    }
}
