<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TracesAggregator\Dto\Objects\TraceChildObject;

class TraceAggregatorChildItemResponse extends AbstractApiResource
{
    private TraceAggregatorTraceResponse $trace;

    public function __construct(TraceChildObject $childItem)
    {
        parent::__construct($childItem);

        $this->trace = new TraceAggregatorTraceResponse($childItem->trace);
    }
}
