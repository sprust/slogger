<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

class ProfilingItemObject
{
    /**
     * @param static[] $callables
     */
    public function __construct(
        public string $call,
        public int $numberOfCalls,
        public float $waitTimeInMs,
        public float $cpuTime,
        public float $memoryUsageInBytes,
        public float $peakMemoryUsageInMb,
        public array $callables,
    ) {
    }
}
