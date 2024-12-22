<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters;

use App\Modules\Trace\Parameters\Profilling\TraceUpdateProfilingObjects;

class TraceUpdateParameters
{
    /**
     * @param string[]|null $tags
     */
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
