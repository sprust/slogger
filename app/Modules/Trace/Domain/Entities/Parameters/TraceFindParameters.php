<?php

namespace App\Modules\Trace\Domain\Entities\Parameters;

use App\Modules\Trace\Domain\Entities\Parameters\Data\TraceDataFilterParameters;

readonly class TraceFindParameters
{
    /**
     * @param int[]                 $serviceIds
     * @param string[]              $types
     * @param string[]              $tags
     * @param TraceSortParameters[] $sort
     */
    public function __construct(
        public int $page = 1,
        public ?int $perPage = null,
        public array $serviceIds = [],
        public ?string $traceId = null,
        public bool $allTracesInTree = false,
        public ?PeriodParameters $loggingPeriod = null,
        public array $types = [],
        public array $tags = [],
        public array $statuses = [],
        public ?float $durationFrom = null,
        public ?float $durationTo = null,
        public ?TraceDataFilterParameters $data = null,
        public ?bool $hasProfiling = null,
        public array $sort = []
    ) {
    }
}
