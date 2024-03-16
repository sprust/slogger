<?php

namespace App\Modules\Service\Http\Resources;

use App\Http\Resources\AbstractApiResource;
use App\Modules\Service\Services\Objects\ServiceObject;

class ServiceResource extends AbstractApiResource
{
    private int $id;
    private string $name;

    public function __construct(ServiceObject $service)
    {
        parent::__construct($service);

        $this->id   = $service->id;
        $this->name = $service->name;
    }
}
