<?php

namespace RrMonitor\Events;

readonly class MonitorWorkersCountSetEvent
{
    public function __construct(
        public string $pluginName,
        public string $operationName,
        public int $count,
        public int $defaultCount,
        public int $currentTotalCount,
        public int $readyCount,
        public int $workingCount
    ) {
    }
}
