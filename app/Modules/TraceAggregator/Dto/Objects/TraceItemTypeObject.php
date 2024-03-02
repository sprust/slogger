<?php

namespace App\Modules\TraceAggregator\Dto\Objects;

readonly class TraceItemTypeObject
{
    public function __construct(
        public string $type,
        public int $count
    ) {
    }
}
