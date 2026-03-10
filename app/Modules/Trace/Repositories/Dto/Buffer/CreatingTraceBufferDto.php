<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Buffer;

use Illuminate\Support\Carbon;

readonly class CreatingTraceBufferDto
{
    /**
     * @param string[] $tags
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $id,
        public int $serviceId,
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public string $status,
        public array $tags,
        public array $data,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public Carbon $loggedAt,
    ) {
    }
}
