<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\Timestamp\TraceTimestampPeriodObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTimestampPeriodResource extends AbstractApiResource
{
    private TraceTimestampPeriodValueResource $period;
    #[OaListItemTypeAttribute(TraceTimestampPeriodTimestampResource::class)]
    private array $timestamps;

    public function __construct(TraceTimestampPeriodObject $resource)
    {
        parent::__construct($resource);

        $this->period     = new TraceTimestampPeriodValueResource($resource->period);
        $this->timestamps = TraceTimestampPeriodTimestampResource::mapIntoMe($resource->timestamps);
    }
}
