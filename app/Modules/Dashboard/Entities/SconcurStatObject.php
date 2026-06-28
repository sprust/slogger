<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Entities;

readonly class SconcurStatObject
{
    /**
     * @param SconcurWorkerObject[] $workers
     */
    public function __construct(
        public bool $available,
        public string $name,
        public int $workersTotal,
        public int $workersHung,
        public float $cpuPercent,
        public int $memoryRssBytes,
        public int $goroutines,
        public int $requestsCompleted,
        public float $requestsAvgMs,
        public int $requestsInFlight,
        public float $masterCpuPercent,
        public int $masterMemoryRssBytes,
        public array $workers,
    ) {
    }
}
