<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Settings\SlowTracesSettingsObject;

class SlowTracesWatcherSettingsResource extends AbstractApiResource
{
    private int $id;
    private float $duration;
    private int $window_minutes;
    private WatcherTraceFilterResource $filter;

    public function __construct(int $id, SlowTracesSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->id             = $id;
        $this->duration       = $resource->duration;
        $this->window_minutes = $resource->windowMinutes;
        $this->filter         = new WatcherTraceFilterResource($resource->traceFilter());
    }
}
