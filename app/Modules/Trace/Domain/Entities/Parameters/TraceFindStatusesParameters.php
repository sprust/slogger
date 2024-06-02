<?php

namespace App\Modules\Trace\Domain\Entities\Parameters;

use App\Modules\Trace\Domain\Entities\Parameters\DataFilter\TraceDataFilterParameters;

readonly class TraceFindStatusesParameters
{
    /**
     * @param int[]    $serviceIds
     * @param string[] $types
     * @param string[] $tags
     */
    public function __construct(
        public array $serviceIds = [],
        public ?string $text = null,
        public ?PeriodParameters $loggingPeriod = null,
        public array $types = [],
        public array $tags = [],
        public ?TraceDataFilterParameters $data = null,
        public ?bool $hasProfiling = null,
    ) {
    }
}
