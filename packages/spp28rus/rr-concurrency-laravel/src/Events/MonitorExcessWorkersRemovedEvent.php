<?php

namespace RrConcurrency\Events;

readonly class MonitorExcessWorkersRemovedEvent
{
    public function __construct(
        public int $count,
        public int $defaultCount,
        public int $currentTotalCount
    ) {
    }
}
