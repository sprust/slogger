<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace;

use Illuminate\Support\Carbon;

readonly class TraceObject
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        public string $id,
        public ?TraceServiceObject $service,
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public string $status,
        public array $tags,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public Carbon $loggedAt,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }
}
