<?php

namespace SLoggerLaravel\Profiling\Dto;

readonly class SLoggerProfilingObject
{
    public function __construct(
        public string $method,
        public SLoggerProfilingDataObject $data
    ) {
    }
}
