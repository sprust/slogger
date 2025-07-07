<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeStringableObject;

class TraceTreeStringableResource extends AbstractApiResource
{
    private string $name;
    private int $traces_count;

    public function __construct(TraceTreeStringableObject $resource)
    {
        parent::__construct($resource);

        $this->name         = $resource->name;
        $this->traces_count = $resource->tracesCount;
    }
}
