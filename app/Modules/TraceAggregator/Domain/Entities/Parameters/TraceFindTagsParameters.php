<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Parameters;

use App\Modules\TraceAggregator\Domain\Entities\Parameters\DataFilter\TraceDataFilterParameters;

readonly class TraceFindTagsParameters
{
    /**
     * @param int[]    $serviceIds
     * @param string[] $types
     */
    public function __construct(
        public array $serviceIds = [],
        public ?string $text = null,
        public ?PeriodParameters $loggingPeriod = null,
        public array $types = [],
        public ?TraceDataFilterParameters $data = null,
        public ?bool $hasProfiling = null,
    ) {
    }
}
