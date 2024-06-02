<?php

namespace App\Modules\Trace\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\Profiling\ProfilingItemDataObject;

class TraceProfilingDataResource extends AbstractApiResource
{
    private string $name;
    private int|float $value;

    public function __construct(ProfilingItemDataObject $resource)
    {
        parent::__construct($resource);

        $this->name  = $resource->name;
        $this->value = $resource->value;
    }
}
