<?php

namespace App\Modules\Trace\Framework\Http\Resources\Timestamp;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampFieldObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTimestampFieldResource extends AbstractApiResource
{
    private string $field;
    #[OaListItemTypeAttribute(TraceTimestampFieldIndicatorResource::class)]
    private array $indicators;

    public function __construct(TraceTimestampFieldObject $resource)
    {
        parent::__construct($resource);

        $this->field      = $resource->field;
        $this->indicators = TraceTimestampFieldIndicatorResource::mapIntoMe($resource->indicators);
    }
}
