<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Modules\TracesAggregator\Dto\Objects\TraceParentObject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TraceAggregatorParentItemResponse extends JsonResource
{
    public function __construct(TraceParentObject $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var TraceParentObject $parentItem */
        $parentItem = $this->resource;

        return [
            'trace' => new TraceAggregatorParentItemTraceResponse($parentItem->trace),
            'types' => TraceAggregatorParentItemTypeResponse::collection($parentItem->types),
        ];
    }
}
