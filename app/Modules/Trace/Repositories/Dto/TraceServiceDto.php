<?php

namespace App\Modules\Trace\Repositories\Dto;

readonly class TraceServiceDto
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
