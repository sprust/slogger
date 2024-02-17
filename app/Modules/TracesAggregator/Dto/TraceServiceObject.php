<?php

namespace App\Modules\TracesAggregator\Dto;

readonly class TraceServiceObject
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
