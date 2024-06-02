<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTypeCountedObject;

class TraceItemTypeResource extends AbstractApiResource
{
    private string $type;
    private int $count;

    public function __construct(TraceTypeCountedObject $type)
    {
        parent::__construct($type);

        $this->type = $type->type;
        $this->count = $type->count;
    }
}
