<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

class ProfilingItemObject
{
    /**
     * @param static[] $callables
     */
    public function __construct(
        public string $id,
        public string $call,
        public int $numberOfCalls,
        public float $waitTimeInUs,
        public float $cpuTime,
        public float $memoryUsageInBytes,
        public float $peakMemoryUsageInBytes,
        public array $callables,
    ) {
    }
}
