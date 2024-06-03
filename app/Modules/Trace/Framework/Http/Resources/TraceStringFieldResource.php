<?php

namespace App\Modules\Trace\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\TraceStringFieldObject;

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
