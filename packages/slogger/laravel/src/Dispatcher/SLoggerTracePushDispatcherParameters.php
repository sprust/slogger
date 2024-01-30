<?php

namespace SLoggerLaravel\Dispatcher;

use Illuminate\Support\Carbon;

class SLoggerTracePushDispatcherParameters
{
    public function __construct(
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public array $tags,
        public array $data,
        public Carbon $loggedAt
    ) {
    }
}
