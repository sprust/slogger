<?php

namespace SLoggerLaravel\Objects;

class SLoggerTraceStopObject
{
    public function __construct(
        public string $traceId,
        public ?array $tags = null,
        public ?array $data = null,
    ) {
    }
}
