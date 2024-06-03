<?php

namespace App\Modules\Trace\Domain\Entities\Objects;

use Illuminate\Support\Carbon;

readonly class TraceObject
{
    public function __construct(
        string $id,
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
