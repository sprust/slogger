<?php

namespace App\Modules\TraceCollector\Adapters\Service\Dto;

class ServiceDto
{
    public function __construct(
        public int $id,
        public string $name
    ) {
    }
}
