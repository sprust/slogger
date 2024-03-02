<?php

namespace App\Modules\TraceAggregator\Dto\Parameters;

use App\Modules\TraceAggregator\Dto\Parameters\DataFilter\TraceDataFilterParameters;

readonly class TraceFindTagsParameters
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
