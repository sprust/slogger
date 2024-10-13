<?php

namespace App\Modules\Trace\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\TraceTypeCountedObject;

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
