<?php

namespace App\Modules\Dashboard\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Entities\ServiceObject;

class ServiceStatServiceResource extends AbstractApiResource
{
    private int $id;
    private string $name;

    public function __construct(ServiceObject $resource)
    {
        parent::__construct($resource);

        $this->id   = $resource->id;
        $this->name = $resource->name;
    }
}
