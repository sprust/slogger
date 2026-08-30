<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Entities;

/**
 * One worker pool of the SConcur master, summed on its own.
 *
 * Since SConcur 0.11 a single master supervises several unlike pools — an HTTP
 * server pool and queue-consumer pools — and their numbers are not comparable, so
 * the totals that matter are per group rather than per master.
 */
readonly class SconcurGroupObject
{
    public function __construct(
        public string $name,
        public int $workersTotal,
        public int $workersHung,
        public float $cpuPercent,
        public int $memoryRssBytes,
        public int $goroutines,
        public ?SconcurWorkObject $work,
    ) {
    }
}
