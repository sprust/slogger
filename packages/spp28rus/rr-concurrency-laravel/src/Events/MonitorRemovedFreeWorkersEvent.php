<?php

namespace RrConcurrency\Events;

readonly class MonitorRemovedFreeWorkersEvent
{
    public function __construct(
        public int $count,
        public int $currentTotalCount
    ) {
    }
}
