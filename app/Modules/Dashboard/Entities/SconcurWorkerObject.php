<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Entities;

class SconcurWorkerObject
{
    public function __construct(
        public int $pid,
        public bool $hung,
        public float $uptimeSeconds,
        public float $cpuPercent,
        public int $memoryRssBytes,
        public int $goroutines,
        public int $requestsInFlight,
        public int $requestsCompleted,
        public float $requestsAvgMs,
    ) {
    }
}
