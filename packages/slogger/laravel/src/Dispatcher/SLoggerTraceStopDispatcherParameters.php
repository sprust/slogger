<?php

namespace SLoggerLaravel\Dispatcher;

class SLoggerTraceStopDispatcherParameters
{
    public function __construct(
        public string $traceId,
        public ?array $tags = null,
        public ?array $data = null,
    ) {
    }
}
