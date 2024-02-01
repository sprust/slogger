<?php

namespace App\Modules\TracesAggregator\Parents\Dto\Parameters;

readonly class TraceParentTypesParameters
{
    public function __construct(
        public int $page = 1,
        public ?int $perPage = null,
    ) {
    }
}
