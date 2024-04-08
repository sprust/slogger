<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceServiceObject;

class TraceServiceResource extends AbstractApiResource
{
    private int $id;
    private string $name;

    public function __construct(TraceServiceObject $service)
    {
        parent::__construct($service);

        $this->id   = $service->id;
        $this->name = $service->name;
    }
}
