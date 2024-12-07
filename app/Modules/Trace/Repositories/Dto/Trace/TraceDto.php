<?php

namespace App\Modules\Trace\Repositories\Dto\Trace;

use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
use Illuminate\Support\Carbon;

readonly class TraceDto
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        public string $id,
        public ?int $serviceId,
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public string $status,
        public array $tags,
        public TraceDataObject $data,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public bool $hasProfiling,
        public Carbon $loggedAt,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }
}
