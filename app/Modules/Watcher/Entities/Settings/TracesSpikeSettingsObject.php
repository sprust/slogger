<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Settings;

readonly class TracesSpikeSettingsObject implements HasTraceFilterInterface
{
    /**
     * @param int $windowMinutes   the recent stretch being judged
     * @param int $baselineMinutes what it is judged against
     * @param int $growthPercent   by how much the window has to exceed the baseline average
     */
    public function __construct(
        public int $windowMinutes = 5,
        public int $baselineMinutes = 60,
        public int $growthPercent = 90,
        private WatcherTraceFilterObject $filter = new WatcherTraceFilterObject()
    ) {
    }

    public function traceFilter(): WatcherTraceFilterObject
    {
        return $this->filter;
    }

    public function timelineDepthMinutes(): int
    {
        return $this->windowMinutes + $this->baselineMinutes;
    }
}
