<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

use Illuminate\Support\Carbon;

readonly class TraceTreeObject
{
    /**
     * @param string[]          $tags
     * @param TraceTreeObject[] $children
     */
    public function __construct(
        public ?TraceServiceObject $serviceObject,
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public string $status,
        public array $tags,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public Carbon $loggedAt,
        public array $children,
        public int $depth,
    ) {
    }
}
