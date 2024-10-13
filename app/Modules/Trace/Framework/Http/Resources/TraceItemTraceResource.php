<?php

namespace App\Modules\Trace\Framework\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\TraceItemTraceObject;
use App\Modules\Trace\Framework\Http\Resources\Data\TraceDataAdditionalFieldResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceItemTraceResource extends AbstractApiResource
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
    private bool $has_profiling;
    #[OaListItemTypeAttribute(TraceDataAdditionalFieldResource::class)]
    private array $additional_fields;
    private string $logged_at;
    private string $created_at;
    private string $updated_at;

    public function __construct(TraceItemTraceObject $trace)
    {
        parent::__construct($trace);

        $this->service         = TraceServiceResource::makeIfNotNull($trace->service);
        $this->trace_id        = $trace->traceId;
        $this->parent_trace_id = $trace->parentTraceId;
        $this->type            = $trace->type;
        $this->status          = $trace->status;
        $this->tags            = $trace->tags;
        $this->duration        = $trace->duration;
        $this->memory          = $trace->memory;
        $this->cpu             = $trace->cpu;

        $this->has_profiling = $trace->hasProfiling;

        $this->additional_fields = TraceDataAdditionalFieldResource::mapIntoMe(
            $trace->additionalFields
        );

        $this->logged_at  = $trace->loggedAt->toDateTimeString('microsecond');
        $this->created_at = $trace->createdAt->toDateTimeString('microsecond');
        $this->updated_at = $trace->updatedAt->toDateTimeString('microsecond');
    }
}
