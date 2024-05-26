<?php

namespace App\Modules\TraceAggregator\Repositories\Dto;

readonly class TraceStringFieldDto
{
    public function __construct(
        public string $name,
        public int $count
    ) {
    }
}
