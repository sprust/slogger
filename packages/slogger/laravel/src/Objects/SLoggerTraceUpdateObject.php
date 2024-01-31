<?php

namespace SLoggerLaravel\Objects;

class SLoggerTraceUpdateObject
{
    public function __construct(
        public string $traceId,
        public ?array $tags = null,
        public ?array $data = null,
    ) {
    }
}
