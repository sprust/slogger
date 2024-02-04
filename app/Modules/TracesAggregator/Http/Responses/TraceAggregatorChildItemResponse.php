<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Modules\TracesAggregator\Dto\Objects\TraceChildObject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TraceAggregatorChildItemResponse extends JsonResource
{
    public function __construct(TraceChildObject $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var TraceChildObject $childItem */
        $childItem = $this->resource;

        return [
            'trace' => new TraceAggregatorTraceResponse($childItem->trace),
        ];
    }
}
