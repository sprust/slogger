<?php

namespace App\Modules\Dashboard\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Entities\ServiceStatObject;

class ServiceStatResource extends AbstractApiResource
{
    private ServiceStatServiceResource $service;
    private string $from;
    private string $to;
    private string $type;
    private string $status;
    private int $count;

    public function __construct(ServiceStatObject $resource)
    {
        parent::__construct($resource);

        $this->service = new ServiceStatServiceResource($resource->service);
        $this->from    = $resource->from->toDateTimeString();
        $this->to      = $resource->to->toDateTimeString();
        $this->type    = $resource->type;
        $this->status  = $resource->status;
        $this->count   = $resource->count;
    }
}
