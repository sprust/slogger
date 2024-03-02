<?php

namespace App\Modules\TraceAggregator\Http\Responses;

use App\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceItemResponse extends AbstractApiResource
{
    private TraceItemTraceResponse $trace;
    #[OaListItemTypeAttribute(TraceItemTypeResponse::class)]
    private array $types;

    public function __construct(TraceItemObject $object)
    {
        parent::__construct($object);

        $this->trace = new TraceItemTraceResponse($object->trace);
        $this->types = TraceItemTypeResponse::mapIntoMe($object->types);
    }
}
