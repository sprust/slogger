<?php

namespace App\Modules\Trace\Repositories\Dto;

class TraceUpdateDto
{
    /**
     * @param string[]|null $tags
     */
    public function __construct(
        public int $serviceId,
        public string $traceId,
        public string $status,
        public ?TraceProfilingDto $profiling,
        public ?array $tags,
        public ?string $data,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu
    ) {
    }
}
