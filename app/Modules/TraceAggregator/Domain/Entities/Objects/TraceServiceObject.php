<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

readonly class TraceServiceObject
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
