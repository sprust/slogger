<?php

namespace App\Modules\TracesAggregator\Dto\Parameters;

use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterParameters;
use App\Modules\TracesAggregator\Dto\PeriodParameters;

readonly class TraceParentsFindByTextParameters
{
    /**
     * @param string[] $types
     */
    public function __construct(
        public ?string $text = null,
        public ?PeriodParameters $loggingPeriod = null,
        public array $types = [],
        public ?TraceDataFilterParameters $data = null,
    ) {
    }
}
