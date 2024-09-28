<?php

namespace RrConcurrency\Events;

readonly class MonitorFreeWorkersRemovedEvent
{
    public function __construct(
        public int $count,
        public int $currentTotalCount
    ) {
    }
}
