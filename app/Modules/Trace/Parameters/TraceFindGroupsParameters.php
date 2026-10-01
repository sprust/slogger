<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters;

use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Enums\TraceGroupFieldEnum;

readonly class TraceFindGroupsParameters
{
    /**
     * @param TraceGroupFieldEnum[] $groupBy
     * @param int[]                 $serviceIds
     * @param string[]              $types
     * @param string[]              $tags
     * @param string[]              $statuses
     */
    public function __construct(
        public PeriodParameters $loggingPeriod,
        public array $groupBy,
        public int $limit,
        public array $serviceIds = [],
        public array $types = [],
        public array $tags = [],
        public array $statuses = [],
        public ?float $durationFrom = null,
        public ?float $durationTo = null,
        public ?TraceDataFilterParameters $data = null
    ) {
    }
}
