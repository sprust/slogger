<?php

namespace App\Modules\Trace\Domain\Entities\Parameters;

use App\Modules\Trace\Domain\Entities\Parameters\Data\TraceDataFilterParameters;

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
        public ?bool $hasProfiling = null,
    ) {
    }
}
