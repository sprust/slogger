<?php

namespace App\Modules\Trace\Repositories\Dto;

readonly class TraceIndexInfoDto
{
    public function __construct(
        public string $name,
        public float $progress
    ) {
    }
}
