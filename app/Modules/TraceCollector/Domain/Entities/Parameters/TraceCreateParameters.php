<?php

namespace App\Modules\TraceCollector\Domain\Entities\Parameters;

use Illuminate\Support\Carbon;

class TraceCreateParameters
{
    public function __construct(
        public int $serviceId,
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public string $status,
        public array $tags,
        public string $data,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public Carbon $loggedAt
    ) {
    }
}
