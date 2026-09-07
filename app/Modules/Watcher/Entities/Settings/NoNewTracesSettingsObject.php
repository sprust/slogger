<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Settings;

readonly class NoNewTracesSettingsObject implements HasTraceFilterInterface
{
    /**
     * @param int $periodMinutes how long the silence has to last
     */
    public function __construct(
        public int $periodMinutes = 10,
        private WatcherTraceFilterObject $filter = new WatcherTraceFilterObject()
    ) {
    }

    public function traceFilter(): WatcherTraceFilterObject
    {
        return $this->filter;
    }
}
