<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeContentResultObject;

class TraceTreeContentResource extends AbstractApiResource
{
    private TraceTreeStateResource $state;
    private ?TraceTreeContentDataResource $content;

    public function __construct(TraceTreeContentResultObject $resource)
    {
        parent::__construct($resource);

        $this->state   = new TraceTreeStateResource($resource->state);
        $this->content = $resource->content
            ? new TraceTreeContentDataResource($resource->content)
            : null;
    }
}
