<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Settings\ManyTracesSettingsObject;

class ManyTracesWatcherSettingsResource extends AbstractApiResource
{
    private int $id;
    private int $window_minutes;
    private int $threshold;
    private WatcherTraceFilterResource $filter;

    public function __construct(int $id, ManyTracesSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->id             = $id;
        $this->window_minutes = $resource->windowMinutes;
        $this->threshold      = $resource->threshold;
        $this->filter         = new WatcherTraceFilterResource($resource->traceFilter());
    }
}
