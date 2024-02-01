<?php

namespace App\Modules\TracesAggregator\Parents\Dto\Objects;

readonly class TraceParentTypeObject
{
    public function __construct(
        public string $type,
        public int $count
    ) {
    }
}
