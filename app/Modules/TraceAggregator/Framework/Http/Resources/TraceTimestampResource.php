<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampsObject;

class TraceTimestampResource extends AbstractApiResource
{
    private string $timestamp;
    private int $count;
    private int $durationPercent;

    public function __construct(TraceTimestampsObject $resource)
    {
        parent::__construct($resource);

        $this->timestamp       = $resource->timestamp;
        $this->count           = $resource->count;
        $this->durationPercent = $resource->durationPercent;
    }
}
