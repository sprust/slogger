<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Modules\TracesAggregator\Dto\Objects\TraceParentTypeObject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TraceAggregatorParentItemTypeResponse extends JsonResource
{
    public function __construct(TraceParentTypeObject $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var TraceParentTypeObject $type */
        $type = $this->resource;

        return [
            'type'  => $type->type,
            'count' => $type->count,
        ];
    }
}
