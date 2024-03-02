<?php

namespace App\Modules\TraceAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemTypeObject;

class TraceItemTypeResponse extends AbstractApiResource
{
    private string $type;
    private int $count;

    public function __construct(TraceItemTypeObject $type)
    {
        parent::__construct($type);

        $this->type = $type->type;
        $this->count = $type->count;
    }
}
