<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Timestamp;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampFieldIndicatorObject;

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
