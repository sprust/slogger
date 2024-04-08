<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTypeObject;

class TraceItemTypeResource extends AbstractApiResource
{
    private string $type;
    private int $count;

    public function __construct(TraceTypeObject $type)
    {
        parent::__construct($type);

        $this->type = $type->type;
        $this->count = $type->count;
    }
}
