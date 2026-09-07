<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Settings;

readonly class SlowTracesSettingsObject implements HasTraceFilterInterface
{
    /**
     * @param float $duration      how long is too long, in the units a trace's `dur` carries
     * @param int   $windowMinutes how far back to look for one
     */
    public function __construct(
        public float $duration = 10,
        public int $windowMinutes = 5,
        private WatcherTraceFilterObject $filter = new WatcherTraceFilterObject()
    ) {
    }

    public function traceFilter(): WatcherTraceFilterObject
    {
        return $this->filter;
    }

    public function timelineDepthMinutes(): int
    {
        return $this->windowMinutes;
    }
}
