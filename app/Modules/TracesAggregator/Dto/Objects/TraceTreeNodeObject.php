<?php

namespace App\Modules\TracesAggregator\Dto\Objects;

use App\Modules\TracesAggregator\Dto\TraceServiceObject;
use Carbon\Carbon;

class TraceTreeNodeObject
{
    /**
     * @param string[] $tags
     * @param TraceTreeNodeObject[] $children
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
