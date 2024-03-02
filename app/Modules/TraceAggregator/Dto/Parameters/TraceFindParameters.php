<?php

namespace App\Modules\TraceAggregator\Dto\Parameters;

use App\Modules\TraceAggregator\Dto\Parameters\DataFilter\TraceDataFilterParameters;

readonly class TraceFindParameters
{
    /**
     * @param string[]              $types
     * @param string[]              $tags
     * @param TraceSortParameters[] $sort
     */
    public function __construct(
        public int $page = 1,
        public ?int $perPage = null,
        public ?string $traceId = null,
        public bool $allTracesInTree = false,
        public ?PeriodParameters $loggingPeriod = null,
        public array $types = [],
        public array $tags = [],
        public array $statuses = [],
        public ?TraceDataFilterParameters $data = null,
        public array $sort = []
    ) {
    }
}
