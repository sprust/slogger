<?php

namespace App\Modules\Trace\Infrastructure\Http\Resources\Timestamp;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;

class TraceMetricIndicatorEnumResource extends AbstractApiResource
{
    private string $name;
    private string $value;

    public function __construct(TraceMetricFieldEnum $resource)
    {
        parent::__construct($resource);

        $this->name  = $resource->name;
        $this->value = $resource->value;
    }
}
