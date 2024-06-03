<?php

namespace App\Modules\Dashboard\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Domain\Entities\Objects\ServiceObject;

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
