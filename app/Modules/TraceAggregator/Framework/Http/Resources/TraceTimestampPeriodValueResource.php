<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Enums\TraceTimestampPeriodEnum;

class TraceTimestampPeriodValueResource extends AbstractApiResource
{
    private string $name;
    private string $value;

    public function __construct(TraceTimestampPeriodEnum $resource)
    {
        parent::__construct($resource);

        $this->name  = $resource->name;
        $this->value = $resource->value;
    }
}
