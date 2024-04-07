<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

readonly class TraceTypeObject
{
    public function __construct(
        public string $type,
        public int $count
    ) {
    }
}
