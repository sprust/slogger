<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

/**
 * A node of a tree opened branch by branch: what TraceTreeResource carries, and how many
 * children the node has.
 */
class TraceTreeChildResource extends AbstractApiResource
{
    private int $service_id;
    private ?string $parent_trace_id;
    private string $trace_id;
    private string $type;
    #[OaListItemTypeAttribute('string')]
    private array $tags;
    private string $status;
    private ?float $duration;
    private ?float $memory;
    private ?float $cpu;
    private string $logged_at;
    private int $children_count;

    public function __construct(TraceTreeRawObject $resource, int $childrenCount)
    {
        parent::__construct($resource);

        $this->service_id      = $resource->serviceId ?: -1;
        $this->parent_trace_id = $resource->parentTraceId;
        $this->trace_id        = $resource->traceId;
        $this->type            = $resource->type;
        $this->tags            = $resource->tags;
        $this->status          = $resource->status;
        $this->duration        = $resource->duration;
        $this->memory          = $resource->memory;
        $this->cpu             = $resource->cpu;
        $this->logged_at       = $resource->loggedAt->toDateTimeString('microsecond');
        $this->children_count  = $childrenCount;
    }
}
