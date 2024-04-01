<?php

namespace App\Modules\TraceAggregator\Adapters\Dto;

readonly class ServiceDto
{
    public function __construct(
        public int $id,
        public string $name
    ) {
    }
}
