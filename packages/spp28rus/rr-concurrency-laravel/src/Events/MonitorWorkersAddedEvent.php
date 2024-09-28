<?php

namespace RrConcurrency\Events;

readonly class MonitorWorkersAddedEvent
{
    public function __construct(
        public int $count,
        public int $currentTotalCount
    ) {
    }
}
