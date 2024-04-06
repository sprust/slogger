<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Parameters;

use App\Modules\TraceAggregator\Domain\Entities\Parameters\DataFilter\TraceDataFilterParameters;

readonly class TraceFindTypesParameters
{
    /**
     * @param int[] $serviceIds
     */
    public function __construct(
        public array $serviceIds = [],
        public ?string $text = null,
        public ?PeriodParameters $loggingPeriod = null,
        public ?TraceDataFilterParameters $data = null,
    ) {
    }
}
