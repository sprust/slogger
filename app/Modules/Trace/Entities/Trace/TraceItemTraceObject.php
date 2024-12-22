<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace;

use App\Modules\Trace\Entities\Trace\Data\TraceDataAdditionalFieldObject;
use Illuminate\Support\Carbon;

readonly class TraceItemTraceObject
{
    /**
     * @param string[]                         $tags
     * @param TraceDataAdditionalFieldObject[] $additionalFields
     */
    public function __construct(
        public ?TraceServiceObject $service,
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public string $status,
        public array $tags,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public bool $hasProfiling,
        public array $additionalFields,
        public Carbon $loggedAt,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }
}
