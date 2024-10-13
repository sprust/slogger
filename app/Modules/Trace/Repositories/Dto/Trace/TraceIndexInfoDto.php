<?php

namespace App\Modules\Trace\Repositories\Dto\Trace;

readonly class TraceIndexInfoDto
{
    public function __construct(
        public string $name,
        public float $progress
    ) {
    }
}
