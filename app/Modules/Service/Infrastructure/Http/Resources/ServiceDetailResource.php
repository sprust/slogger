<?php

declare(strict_types=1);

namespace App\Modules\Service\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Service\Entities\ServiceObject;

class ServiceDetailResource extends AbstractApiResource
{
    private int $id;
    private string $name;
    private string $unique_key;
    private string $api_token;
    private int $position;
    private string $created_at;

    public function __construct(ServiceObject $service)
    {
        parent::__construct($service);

        $this->id         = $service->id;
        $this->name       = $service->name;
        $this->unique_key = $service->uniqueKey;
        $this->api_token  = $service->apiToken;
        $this->position   = $service->position;
        $this->created_at = $service->createdAt->toDateTimeString();
    }
}
