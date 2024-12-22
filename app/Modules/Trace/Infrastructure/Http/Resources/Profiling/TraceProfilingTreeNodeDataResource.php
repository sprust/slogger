<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Profiling;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Profiling\ProfilingItemDataObject;

class TraceProfilingTreeNodeDataResource extends AbstractApiResource
{
    private string $name;
    private int|float $value;
    private float $weight_percent;

    public function __construct(ProfilingItemDataObject $resource)
    {
        parent::__construct($resource);

        $this->name           = $resource->name;
        $this->value          = $resource->value;
        $this->weight_percent = $resource->weightPercent;
    }
}
