<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Timestamp;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampPeriodObject;
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
