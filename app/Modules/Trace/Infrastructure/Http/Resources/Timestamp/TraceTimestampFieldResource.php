<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Timestamp;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampFieldObject;
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
