<?php

namespace App\Modules\TraceAggregator\Dto\Objects;

readonly class TraceServiceObject
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
