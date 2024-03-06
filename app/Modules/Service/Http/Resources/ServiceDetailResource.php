<?php

namespace App\Modules\Service\Http\Resources;

use App\Http\Resources\AbstractApiResource;
use App\Modules\Service\Dto\Objects\ServiceDetailObject;

class ServiceDetailResource extends AbstractApiResource
{
    private int $id;
    private string $name;
    private string $api_token;

    public function __construct(ServiceDetailObject $service)
    {
        parent::__construct($service);

        $this->id        = $service->id;
        $this->name      = $service->name;
        $this->api_token = $service->apiToken;
    }
}
