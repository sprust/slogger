<?php

namespace App\Modules\Trace\Framework\Http\Resources\Timestamp;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampsObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTimestampResource extends AbstractApiResource
{
    private string $timestamp;
    private string $timestamp_to;
    #[OaListItemTypeAttribute(TraceTimestampFieldResource::class)]
    private array $fields;

    public function __construct(TraceTimestampsObject $resource)
    {
        parent::__construct($resource);

        $this->timestamp    = $resource->timestamp->toDateTimeString();
        $this->timestamp_to = $resource->timestampTo->toDateTimeString();
        $this->fields       = TraceTimestampFieldResource::mapIntoMe($resource->fields);
    }
}
