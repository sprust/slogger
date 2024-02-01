<?php

namespace App\Modules\TracesAggregator\Parents\Dto\Objects\Parents;

readonly class TraceParentTypeObject
{
    public function __construct(
        public string $type,
        public int $count
    ) {
    }
}
