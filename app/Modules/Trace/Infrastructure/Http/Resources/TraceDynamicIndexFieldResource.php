<?php

namespace App\Modules\Trace\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexFieldObject;

class TraceDynamicIndexFieldResource extends AbstractApiResource
{
    private string $name;
    private string $title;

    public function __construct(TraceDynamicIndexFieldObject $resource)
    {
        parent::__construct($resource);

        $this->name  = $resource->name;
        $this->title = $resource->title;
    }
}
