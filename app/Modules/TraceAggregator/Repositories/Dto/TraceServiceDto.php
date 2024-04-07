<?php

namespace App\Modules\TraceAggregator\Repositories\Dto;

readonly class TraceServiceDto
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
