<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters;

use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;

readonly class TraceFindTypesParameters
{
    /**
     * @param int[]    $serviceIds
     * @param string[] $tags
     * @param string[] $statuses
     */
    public function __construct(
        public array $serviceIds = [],
        public ?string $text = null,
        public ?PeriodParameters $loggingPeriod = null,
        public array $tags = [],
        public array $statuses = [],
        public ?float $durationFrom = null,
        public ?float $durationTo = null,
        public ?float $memoryFrom = null,
        public ?float $memoryTo = null,
        public ?float $cpuFrom = null,
        public ?float $cpuTo = null,
        public ?TraceDataFilterParameters $data = null,
        public ?bool $hasProfiling = null,
    ) {
    }
}
