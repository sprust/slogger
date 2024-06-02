<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceStringFieldObject;

class TraceStringFieldResource extends AbstractApiResource
{
    private string $name;
    private int $count;

    public function __construct(TraceStringFieldObject $resource)
    {
        parent::__construct($resource);

        $this->name  = $resource->name;
        $this->count = $resource->count;
    }
}
