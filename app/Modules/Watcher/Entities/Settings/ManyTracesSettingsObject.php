<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Settings;

readonly class ManyTracesSettingsObject implements HasTraceFilterInterface
{
    /**
     * @param int $windowMinutes how far back to count
     * @param int $threshold     how many traces in that stretch are too many
     */
    public function __construct(
        public int $windowMinutes = 5,
        public int $threshold = 1000,
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

    public function toArray(): array
    {
        return [
            'window_minutes' => $this->windowMinutes,
            'threshold'      => $this->threshold,
            'filter'         => $this->filter->toArray(),
        ];
    }
}
