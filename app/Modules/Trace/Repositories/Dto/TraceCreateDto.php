<?php

namespace App\Modules\Trace\Repositories\Dto;

use App\Modules\Trace\Repositories\Dto\Timestamp\TraceTimestampMetricDto;
use Illuminate\Support\Carbon;

class TraceCreateDto
{
    /**
     * @param string[]                  $tags
     * @param TraceTimestampMetricDto[] $timestamps
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
