<?php

namespace App\Modules\TracesAggregator\Parents\Dto\Objects\Parents;

readonly class TracesAggregatorParentTypeObject
{
    public function __construct(
        public string $type,
        public int $count
    ) {
    }
}
