<?php

namespace App\Modules\Traces\Dto\Parameters;

class TraceUpdateParameters
{
    public function __construct(
        public int $serviceId,
        public string $traceId,
        public string $status,
        public ?TraceUpdateProfilingObjects $profiling,
        public ?array $tags,
        public ?string $data,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu
    ) {
    }
}
