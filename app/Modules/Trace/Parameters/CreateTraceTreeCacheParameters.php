<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters;

use Illuminate\Support\Carbon;

readonly class CreateTraceTreeCacheParameters
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        public ?int $serviceId,
        public ?string $parentTraceId,
        public string $traceId,
        public string $type,
        public array $tags,
        public string $status,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public Carbon $loggedAt,
    ) {
    }
}
