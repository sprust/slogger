<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

readonly class TraceItemTypeObject
{
    public function __construct(
        public string $type,
        public int $count
    ) {
    }
}
