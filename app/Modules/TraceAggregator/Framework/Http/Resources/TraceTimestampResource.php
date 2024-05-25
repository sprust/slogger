<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampsObject;

class TraceTimestampResource extends AbstractApiResource
{
    private string $timestamp;
    private string $timestamp_to;
    private int $count;
    private int $durationPercent;

    public function __construct(TraceTimestampsObject $resource)
    {
        parent::__construct($resource);

        $this->timestamp       = $resource->timestamp->toDateTimeString();
        $this->timestamp_to    = $resource->timestampTo->toDateTimeString();
        $this->count           = $resource->count;
        $this->durationPercent = $resource->durationPercent;
    }
}