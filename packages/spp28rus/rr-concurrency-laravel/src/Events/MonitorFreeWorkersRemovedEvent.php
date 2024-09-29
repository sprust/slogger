<?php

namespace RrConcurrency\Events;

readonly class MonitorFreeWorkersRemovedEvent
{
    public function __construct(
        public string $pluginName,
        public int $count,
        public int $defaultCount,
        public int $currentTotalCount
    ) {
    }
}
