<?php

namespace App\Modules\TraceCollector\Domain\Entities\Parameters;

use App\Modules\TraceAggregator\Domain\Entities\Objects\Timestamp\TraceTimestampMetricObject;
use Illuminate\Support\Carbon;

class TraceCreateParameters
{
    /**
     * @param string[]                     $tags
     * @param TraceTimestampMetricObject[] $timestamps
     */
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
        public array $timestamps,
        public Carbon $loggedAt
    ) {
    }
}
