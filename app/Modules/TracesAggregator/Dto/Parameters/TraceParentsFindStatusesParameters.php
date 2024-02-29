<?php

namespace App\Modules\TracesAggregator\Dto\Parameters;

use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterParameters;
use App\Modules\TracesAggregator\Dto\PeriodParameters;

readonly class TraceParentsFindStatusesParameters
{
    /**
     * @param string[] $types
     * @param string[] $tags
     */
    public function __construct(
        public ?string $text = null,
        public ?PeriodParameters $loggingPeriod = null,
        public array $types = [],
        public array $tags = [],
        public ?TraceDataFilterParameters $data = null,
    ) {
    }
}
