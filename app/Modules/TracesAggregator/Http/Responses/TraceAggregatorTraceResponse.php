<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Modules\TracesAggregator\Dto\TraceObject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TraceAggregatorTraceResponse extends JsonResource
{
    public function __construct(TraceObject $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var TraceObject $trace */
        $trace = $this->resource;

        return [
            'service_id'      => $trace->serviceId,
            'trace_id'        => $trace->traceId,
            'parent_trace_id' => $trace->parentTraceId,
            'type'            => $trace->type,
            'tags'            => $trace->tags,
            'data'            => $trace->data,
            'logged_at'       => $trace->loggedAt->toDateTimeString('microsecond'),
            'created_at'      => $trace->createdAt->toDateTimeString('microsecond'),
            'updated_at'      => $trace->updatedAt->toDateTimeString('microsecond'),
        ];
    }
}
