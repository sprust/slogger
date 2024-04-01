<?php

namespace App\Modules\TraceAggregator\Repositories\Dto;

class ServiceStatDto
{
    public function __construct(
        public int $serviceId,
        public string $type,
        public string $tag,
        public string $status,
        public int $count
    ) {
    }
}
