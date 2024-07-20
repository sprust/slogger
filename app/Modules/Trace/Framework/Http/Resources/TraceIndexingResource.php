<?php

namespace App\Modules\Trace\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\TraceIndexObject;

class TraceIndexingResource extends AbstractApiResource
{
    private string $name;
    private bool $in_process;
    private bool $created;

    public function __construct(TraceIndexObject $resource)
    {
        parent::__construct($resource);

        $this->name       = $resource->name;
        $this->in_process = $resource->inProcess;
        $this->created    = $resource->created;
    }
}
