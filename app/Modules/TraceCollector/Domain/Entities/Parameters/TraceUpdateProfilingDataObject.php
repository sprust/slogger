<?php

namespace App\Modules\TraceCollector\Domain\Entities\Parameters;

readonly class TraceUpdateProfilingDataObject
{
    public function __construct(
        public int $numberOfCalls,
        public float $waitTimeInMs,
        public float $cpuTime,
        public float $memoryUsageInBytes,
        public float $peakMemoryUsageInMb
    ) {
    }
}
