<?php

namespace App\Modules\TracesAggregator\Parents\Dto\Parameters;

use App\Modules\TracesAggregator\Dto\PeriodParameters;

readonly class TraceParentTypesParameters
{
    public function __construct(
        public int $page = 1,
        public ?int $perPage = null,
        public ?PeriodParameters $loggingPeriod = null
    ) {
    }
}
