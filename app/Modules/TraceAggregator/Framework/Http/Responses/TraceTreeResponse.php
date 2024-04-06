<?php

namespace App\Modules\TraceAggregator\Framework\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTreeObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTreeResponse extends AbstractApiResource
{
    private ?TraceServiceResponse $service;
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
    #[OaListItemTypeAttribute(TraceTreeResponse::class, isRecursive: true)]
    private array $children;
    private int $depth;

    public function __construct(TraceTreeObject $tree)
    {
        parent::__construct($tree);

        $this->service         = $tree->serviceObject
            ? new TraceServiceResponse(
                $tree->serviceObject
            )
            : null;
        $this->trace_id        = $tree->traceId;
        $this->parent_trace_id = $tree->parentTraceId;
        $this->type            = $tree->type;
        $this->status          = $tree->status;
        $this->tags            = $tree->tags;
        $this->duration        = $tree->duration;
        $this->memory          = $tree->memory;
        $this->cpu             = $tree->cpu;
        $this->logged_at       = $tree->loggedAt->toDateTimeString('microsecond');
        $this->children        = TraceTreeResponse::mapIntoMe($tree->children);
        $this->depth           = $tree->depth;
    }
}
