<?php

namespace RrConcurrency\Events;

readonly class MonitorAddedWorkersEvent
{
    public function __construct(
        public int $count,
        public int $currentTotalCount
    ) {
    }
}
