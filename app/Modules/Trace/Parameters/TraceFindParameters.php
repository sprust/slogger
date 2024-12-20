<?php

namespace App\Modules\Trace\Parameters;

use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;

readonly class TraceFindParameters
{
    /**
     * @param int[]                 $serviceIds
     * @param string[]              $types
     * @param string[]              $tags
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
        public ?float $memoryFrom = null,
        public ?float $memoryTo = null,
        public ?float $cpuFrom = null,
        public ?float $cpuTo = null,
        public ?TraceDataFilterParameters $data = null,
        public ?bool $hasProfiling = null
    ) {
    }
}
