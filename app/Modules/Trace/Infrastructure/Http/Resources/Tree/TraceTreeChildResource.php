<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeChildObject;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceServiceResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTreeChildResource extends AbstractApiResource
{
    private string $id;
    private ?TraceServiceResource $service;
    private string $trace_id;
    private ?string $parent_trace_id;
    private string $type;
    private string $status;
    #[OaListItemTypeAttribute('string')]
    private array $tags;
    private ?float $duration;
    private ?float $memory;
    private ?float $cpu;
    private string $logged_at;
    private bool $has_children;

    public function __construct(TraceTreeChildObject $resource)
    {
        parent::__construct($resource);

        $this->id              = $resource->id;
        $this->service         = TraceServiceResource::makeIfNotNull($resource->service);
        $this->trace_id        = $resource->traceId;
        $this->parent_trace_id = $resource->parentTraceId;
        $this->type            = $resource->type;
        $this->status          = $resource->status;
        $this->tags            = $resource->tags;
        $this->duration        = $resource->duration;
        $this->memory          = $resource->memory;
        $this->cpu             = $resource->cpu;
        $this->logged_at       = $resource->loggedAt->toDateTimeString('microsecond');
        $this->has_children    = $resource->hasChildren;
    }
}
