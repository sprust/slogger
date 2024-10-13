<?php

namespace App\Modules\Trace\Framework\Http\Resources\Profiling;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\Profiling\Tree\ProfilingTreeNodeObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceProfilingTreeNodeResource extends AbstractApiResource
{
    private int $id;
    private string $calling;
    #[OaListItemTypeAttribute(TraceProfilingTreeNodeDataResource::class)]
    private array $data;
    private ?int $recursionNodeId;
    #[OaListItemTypeAttribute(TraceProfilingTreeNodeResource::class, isRecursive: true)]
    private ?array $children;

    public function __construct(ProfilingTreeNodeObject $resource)
    {
        parent::__construct($resource);

        $this->id              = $resource->id;
        $this->calling         = $resource->calling;
        $this->data            = TraceProfilingTreeNodeDataResource::mapIntoMe($resource->data);
        $this->recursionNodeId = $resource->recursionNodeId;
        $this->children        = $resource->children
            ? TraceProfilingTreeNodeResource::mapIntoMe($resource->children)
            : null;
    }
}
