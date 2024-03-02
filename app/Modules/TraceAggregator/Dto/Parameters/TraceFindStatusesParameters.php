<?php

namespace App\Modules\TraceAggregator\Dto\Parameters;

use App\Modules\TraceAggregator\Dto\Parameters\DataFilter\TraceDataFilterParameters;

readonly class TraceFindStatusesParameters
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
