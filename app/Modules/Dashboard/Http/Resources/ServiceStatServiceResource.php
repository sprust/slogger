<?php

namespace App\Modules\Dashboard\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Services\ServiceStat\Objects\ServiceStatServiceObject;

class ServiceStatServiceResource extends AbstractApiResource
{
    private int $id;
    private string $name;

    public function __construct(ServiceStatServiceObject $resource)
    {
        parent::__construct($resource);

        $this->id   = $resource->id;
        $this->name = $resource->name;
    }
}
