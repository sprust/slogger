<?php

namespace App\Modules\Trace\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\TraceServiceObject;

class TraceServiceResource extends AbstractApiResource
{
    private int $id;
    private string $name;

    public function __construct(TraceServiceObject $service)
    {
        parent::__construct($service);

        $this->id   = $service->id;
        $this->name = $service->name;
    }
}
