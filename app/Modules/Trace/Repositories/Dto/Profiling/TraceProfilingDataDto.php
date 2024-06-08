<?php

namespace App\Modules\Trace\Repositories\Dto\Profiling;

readonly class TraceProfilingDataDto
{
    public function __construct(
        public string $name,
        public int|float $value,
    ) {
    }
}
