<?php

namespace App\Modules\Service\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\Service\Domain\Entities\Objects\ServiceObject;

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
