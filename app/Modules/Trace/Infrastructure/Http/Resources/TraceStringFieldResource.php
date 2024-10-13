<?php

namespace App\Modules\Trace\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;

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
