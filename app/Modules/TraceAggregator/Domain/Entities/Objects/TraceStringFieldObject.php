<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

readonly class TraceStringFieldObject
{
    public function __construct(
        public string $name,
        public int $count
    ) {
    }
}
