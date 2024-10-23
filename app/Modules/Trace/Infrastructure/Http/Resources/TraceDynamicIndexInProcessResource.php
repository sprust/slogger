<?php

namespace App\Modules\Trace\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\TraceIndexInfoObject;

class TraceDynamicIndexInProcessResource extends AbstractApiResource
{
    private string $name;
    private float $progress;

    public function __construct(TraceIndexInfoObject $resource)
    {
        parent::__construct($resource);

        $this->name = $resource->name;
        $this->progress = $resource->progress;
    }
}
