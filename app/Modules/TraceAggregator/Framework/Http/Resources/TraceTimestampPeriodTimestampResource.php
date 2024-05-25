<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampObject;

class TraceTimestampPeriodTimestampResource extends AbstractApiResource
{
    private string $value;
    private string $title;

    public function __construct(TraceTimestampObject $resource)
    {
        parent::__construct($resource);

        $this->value = $resource->value;
        $this->title = $resource->title;
    }
}
