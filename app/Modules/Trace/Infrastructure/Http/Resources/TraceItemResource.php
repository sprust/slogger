<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\TraceItemObject;

class TraceItemResource extends AbstractApiResource
{
    private TraceItemTraceResource $trace;

    public function __construct(TraceItemObject $object)
    {
        parent::__construct($object);

        $this->trace = new TraceItemTraceResource($object->trace);
    }
}
