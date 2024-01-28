<?php

namespace SLoggerLaravel\Dispatcher;

class TraceStopDispatcherParameters
{
    public function __construct(
        public string $traceId,
        public ?array $tags = null,
        public ?array $data = null,
    ) {
    }
}
