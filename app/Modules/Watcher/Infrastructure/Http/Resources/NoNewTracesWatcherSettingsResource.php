<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Settings\NoNewTracesSettingsObject;

class NoNewTracesWatcherSettingsResource extends AbstractApiResource
{
    private int $id;
    private int $period_minutes;
    private WatcherTraceFilterResource $filter;

    public function __construct(int $id, NoNewTracesSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->id             = $id;
        $this->period_minutes = $resource->periodMinutes;
        $this->filter         = new WatcherTraceFilterResource($resource->traceFilter());
    }
}
