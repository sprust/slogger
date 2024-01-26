<?php

namespace SLoggerLaravel\Dispatcher;

use Illuminate\Support\Carbon;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;

class TraceDispatcherParameters
{
    public function __construct(
        public string $traceId,
        public ?string $parentTraceId,
        public SLoggerTraceTypeEnum $type,
        public array $tags,
        public array $data,
        public Carbon $loggedAt
    ) {
    }
}
