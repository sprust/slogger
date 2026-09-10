<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\TracesSpikeEventSettingsObject;

/** What the watcher was set to when it went off. */
class TracesSpikeEventSettingsResource extends AbstractApiResource
{
    private int $window_minutes;
    private int $baseline_minutes;
    private int $growth_percent;

    public function __construct(TracesSpikeEventSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->window_minutes   = $resource->windowMinutes;
        $this->baseline_minutes = $resource->baselineMinutes;
        $this->growth_percent   = $resource->growthPercent;
    }
}
