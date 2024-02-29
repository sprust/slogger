<?php

namespace App\Modules\TracesAggregator\Dto\Parameters;

use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterParameters;
use App\Modules\TracesAggregator\Dto\PeriodParameters;

readonly class TraceParentsFindTypesParameters
{
    public function __construct(
        public ?string $text = null,
        public ?PeriodParameters $loggingPeriod = null,
        public ?TraceDataFilterParameters $data = null,
    ) {
    }
}
