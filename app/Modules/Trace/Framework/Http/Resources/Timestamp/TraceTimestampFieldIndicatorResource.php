<?php

namespace App\Modules\Trace\Framework\Http\Resources\Timestamp;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampFieldIndicatorObject;

class TraceTimestampFieldIndicatorResource extends AbstractApiResource
{
    private string $name;
    private int|float $value;

    public function __construct(TraceTimestampFieldIndicatorObject $resource)
    {
        parent::__construct($resource);

        $this->name  = $resource->name;
        $this->value = $resource->value;
    }
}
