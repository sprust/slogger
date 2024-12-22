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
    private ?string $parent_trace_id;
    private string $type;
    private string $status;
    #[OaListItemTypeAttribute('string')]
    private array $tags;
    private ?float $duration;
    private ?float $memory;
    private ?float $cpu;
    private string $logged_at;
    #[OaListItemTypeAttribute(TraceTreeResource::class, isRecursive: true)]
    private array $children;
    private int $depth;

    public function __construct(TraceTreeObject $tree)
    {
        parent::__construct($tree);

        $this->service         = TraceServiceResource::makeIfNotNull($tree->service);
        $this->trace_id        = $tree->traceId;
        $this->parent_trace_id = $tree->parentTraceId;
        $this->type            = $tree->type;
        $this->status          = $tree->status;
        $this->tags            = $tree->tags;
        $this->duration        = $tree->duration;
        $this->memory          = $tree->memory;
        $this->cpu             = $tree->cpu;
        $this->logged_at       = $tree->loggedAt->toDateTimeString('microsecond');
        $this->children        = TraceTreeResource::mapIntoMe($tree->children);
        $this->depth           = $tree->depth;
    }
}
