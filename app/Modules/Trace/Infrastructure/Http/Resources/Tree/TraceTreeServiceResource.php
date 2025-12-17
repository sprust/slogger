<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeServiceObject;

class TraceTreeServiceResource extends AbstractApiResource
{
    private int $id;
    private string $name;
    private int $traces_count;

    public function __construct(TraceTreeServiceObject $resource)
    {
        parent::__construct($resource);

        $this->id           = $resource->id;
        $this->name         = $resource->name;
        $this->traces_count = $resource->tracesCount;
    }
}
