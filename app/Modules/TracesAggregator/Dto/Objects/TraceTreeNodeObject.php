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
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public array $tags,
        public ?TraceServiceObject $serviceObject,
        public Carbon $loggedAt,
        public array $children,
    ) {
    }
}
