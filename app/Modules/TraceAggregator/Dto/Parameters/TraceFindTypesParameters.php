<?php

namespace App\Modules\TraceAggregator\Dto\Parameters;

use App\Modules\TraceAggregator\Dto\Parameters\DataFilter\TraceDataFilterParameters;

readonly class TraceFindTypesParameters
{
    public function __construct(
        public ?string $text = null,
        public ?PeriodParameters $loggingPeriod = null,
        public ?TraceDataFilterParameters $data = null,
    ) {
    }
}
