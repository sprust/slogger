<?php

namespace RrConcurrency\Events;

readonly class MonitorExcessWorkersRemovedEvent
{
    public function __construct(
        public string $pluginName,
        public int $count,
        public int $defaultCount,
        public int $currentTotalCount
    ) {
    }
}
