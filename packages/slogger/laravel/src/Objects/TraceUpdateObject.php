<?php

namespace SLoggerLaravel\Objects;

use SLoggerLaravel\Profiling\Dto\ProfilingObjects;

class TraceUpdateObject
{
    /**
     * @param string[]|null             $tags
     * @param array<string, mixed>|null $data
     */
    public function __construct(
        public string $traceId,
        public string $status,
        public ?ProfilingObjects $profiling = null,
        public ?array $tags = null,
        public ?array $data = null,
        public ?float $duration = null,
        public ?float $memory = null,
        public ?float $cpu = null,
    ) {
    }
}
