<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeObject;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceServiceResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTreeResource extends AbstractApiResource
{
    private ?TraceServiceResource $service;
    private string $trace_id;
    private string $type;
    #[OaListItemTypeAttribute('string')]
    private array $tags;
    private string $status;
    private ?float $duration;
    private ?float $memory;
    private ?float $cpu;
    private string $logged_at;
    private int $depth;

    public function __construct(TraceTreeObject $resource)
    {
        parent::__construct($resource);

        $this->service   = TraceServiceResource::makeIfNotNull($resource->service);
        $this->trace_id  = $resource->traceId;
        $this->type      = $resource->type;
        $this->tags      = $resource->tags;
        $this->status    = $resource->status;
        $this->duration  = $resource->duration;
        $this->memory    = $resource->memory;
        $this->cpu       = $resource->cpu;
        $this->logged_at = $resource->loggedAt->toDateTimeString('microsecond');
        $this->depth     = $resource->depth;
    }
}
