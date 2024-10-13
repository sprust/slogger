<?php

namespace App\Modules\Trace\Framework\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\TraceDynamicIndexFieldObject;

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
