<?php

namespace App\Modules\Trace\Repositories\Dto;

readonly class TraceProfilingDataDto
{
    public function __construct(
        public string $name,
        public int|float $value,
    ) {
    }
}
