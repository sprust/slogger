<?php

namespace RrConcurrency\Events;

readonly class MonitorRemovedExcessWorkersEvent
{
    public function __construct(
        public int $count,
        public int $currentTotalCount
    ) {
    }
}
