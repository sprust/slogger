<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\TracesSpikeEventMeasuredObject;

/** What the checker saw. */
class TracesSpikeEventMeasuredResource extends AbstractApiResource
{
    private int $window_count;
    private float $window_per_minute;
    private float $baseline_per_minute;
    private float $growth_percent;

    public function __construct(TracesSpikeEventMeasuredObject $resource)
    {
        parent::__construct($resource);

        $this->window_count        = $resource->windowCount;
        $this->window_per_minute   = $resource->windowPerMinute;
        $this->baseline_per_minute = $resource->baselinePerMinute;
        $this->growth_percent      = $resource->growthPercent;
    }
}
