<?php

namespace App\Modules\TraceAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Dto\Objects\ServiceStatObject;

class ServiceStatResponse extends AbstractApiResource
{
    private ?TraceServiceResponse $service;
    private string $type;
    private string $status;
    private string $tag;
    private int $count;

    public function __construct(ServiceStatObject $trace)
    {
        parent::__construct($trace);

        $this->service = TraceServiceResponse::makeIfNotNull($trace->service);
        $this->type    = $trace->type;
        $this->status  = $trace->status;
        $this->tag     = $trace->tag;
        $this->count   = $trace->count;
    }
}
