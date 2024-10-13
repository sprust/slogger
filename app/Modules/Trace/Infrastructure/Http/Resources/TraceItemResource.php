<?php

namespace App\Modules\Trace\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\TraceItemObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceItemResource extends AbstractApiResource
{
    private TraceItemTraceResource $trace;
    #[OaListItemTypeAttribute(TraceItemTypeResource::class)]
    private array $types;

    public function __construct(TraceItemObject $object)
    {
        parent::__construct($object);

        $this->trace = new TraceItemTraceResource($object->trace);
        $this->types = TraceItemTypeResource::mapIntoMe($object->types);
    }
}
