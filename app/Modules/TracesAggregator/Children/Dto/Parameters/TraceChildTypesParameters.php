<?php

namespace App\Modules\TracesAggregator\Children\Dto\Parameters;

use App\Modules\TracesAggregator\Dto\PeriodParameters;

readonly class TraceChildTypesParameters
{
    public function __construct(
        public int $page = 1,
        public ?int $perPage = null,
        public ?PeriodParameters $loggingPeriod = null
    ) {
    }
}
