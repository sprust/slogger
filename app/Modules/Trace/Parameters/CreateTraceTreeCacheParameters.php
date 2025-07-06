<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters;

use Illuminate\Support\Carbon;

readonly class CreateTraceTreeCacheParameters
{
    public function __construct(
        public ?int $serviceId,
        public ?string $traceId,
        public string $type,
        public string $status,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public Carbon $loggedAt,
    ) {
    }
}
