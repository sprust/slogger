<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TracesAggregator\Dto\Objects\TraceParentTypeObject;

class TraceAggregatorParentItemTypeResponse extends AbstractApiResource
{
    private string $type;
    private int $count;

    public function __construct(TraceParentTypeObject $type)
    {
        parent::__construct($type);

        $this->type = $type->type;
        $this->count = $type->count;
    }
}
