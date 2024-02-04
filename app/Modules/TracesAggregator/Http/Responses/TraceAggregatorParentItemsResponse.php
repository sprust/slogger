<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\PaginatorInfoResource;
use App\Modules\TracesAggregator\Dto\Objects\TraceParentObjects;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TraceAggregatorParentItemsResponse extends JsonResource
{
    public function __construct(TraceParentObjects $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var TraceParentObjects $parents */
        $parents = $this->resource;

        return [
            'items'     => TraceAggregatorParentItemResponse::collection($parents->items),
            'paginator' => new PaginatorInfoResource($parents->paginationInfo),
        ];
    }
}
