<?php

declare(strict_types=1);

namespace App\Modules\Service\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Service\Entities\ServiceObject;

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
