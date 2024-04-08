<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceItemObject;
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
