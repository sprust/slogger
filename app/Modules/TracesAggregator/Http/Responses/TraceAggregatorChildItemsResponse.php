<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\PaginatorInfoResource;
use App\Modules\TracesAggregator\Dto\Objects\TraceChildObjects;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TraceAggregatorChildItemsResponse extends JsonResource
{
    public function __construct(TraceChildObjects $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var TraceChildObjects $children */
        $children = $this->resource;

        return [
            'items'     => TraceAggregatorChildItemResponse::collection($children->items),
            'paginator' => new PaginatorInfoResource($children->paginationInfo),
        ];
    }
}
