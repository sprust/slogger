<?php

namespace App\Modules\Trace\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampsObject;

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
