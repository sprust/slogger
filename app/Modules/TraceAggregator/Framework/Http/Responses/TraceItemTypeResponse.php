<?php

namespace App\Modules\TraceAggregator\Framework\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceItemTypeObject;

class TraceItemTypeResponse extends AbstractApiResource
{
    private string $type;
    private int $count;

    public function __construct(TraceItemTypeObject $type)
    {
        parent::__construct($type);

        $this->type = $type->type;
        $this->count = $type->count;
    }
}
