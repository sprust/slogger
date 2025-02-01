<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Trace;

use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;

readonly class TraceHubDto
{
    /**
     * @param string[]                   $tags
     * @param array<string, mixed>       $data
     * @param array<string, mixed>       $profiling
     * @param array<string, UTCDateTime> $timestamps
     */
    public function __construct(
        public string $id,
        public ?int $serviceId,
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public string $status,
        public array $tags,
        public array $data,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public bool $hasProfiling,
        public ?array $profiling,
        public array $timestamps,
        public Carbon $loggedAt,
        public Carbon $createdAt,
        public Carbon $updatedAt,
        public bool $inserted,
        public bool $updated,
    ) {
    }
}
