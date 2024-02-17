<?php

namespace App\Modules\TracesAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TracesAggregator\Dto\TraceServiceObject;

class TraceAggregatorTraceServiceResponse extends AbstractApiResource
{
    private int $id;
    private string $name;

    public function __construct(TraceServiceObject $serviceObject)
    {
        parent::__construct($serviceObject);

        $this->id   = $serviceObject->id;
        $this->name = $serviceObject->name;
    }
}
