<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Events;

/** What the checker saw. */
readonly class TracesSpikeEventMeasuredObject
{
    public function __construct(
        public int $windowCount,
        public float $windowPerMinute,
        public float $baselinePerMinute,
        public float $growthPercent
    ) {
    }
}
