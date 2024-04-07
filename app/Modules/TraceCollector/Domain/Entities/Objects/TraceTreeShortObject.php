<?php

namespace App\Modules\TraceCollector\Domain\Entities\Objects;

use Illuminate\Support\Carbon;

readonly class TraceTreeShortObject
{
    public function __construct(
        public string $traceId,
        public ?string $parentTraceId,
        public Carbon $loggedAt
    ) {
    }
}
