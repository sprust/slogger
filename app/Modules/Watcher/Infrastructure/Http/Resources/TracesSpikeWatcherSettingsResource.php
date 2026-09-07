<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Settings\TracesSpikeSettingsObject;

class TracesSpikeWatcherSettingsResource extends AbstractApiResource
{
    private int $id;
    private int $window_minutes;
    private int $baseline_minutes;
    private int $growth_percent;
    private WatcherTraceFilterResource $filter;

    public function __construct(int $id, TracesSpikeSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->id               = $id;
        $this->window_minutes   = $resource->windowMinutes;
        $this->baseline_minutes = $resource->baselineMinutes;
        $this->growth_percent   = $resource->growthPercent;
        $this->filter           = new WatcherTraceFilterResource($resource->traceFilter());
    }
}
