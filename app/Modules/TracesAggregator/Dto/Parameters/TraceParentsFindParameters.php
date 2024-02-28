<?php

namespace App\Modules\TracesAggregator\Dto\Parameters;

use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterParameters;
use App\Modules\TracesAggregator\Dto\PeriodParameters;

readonly class TraceParentsFindParameters
{
    /**
     * @param string[] $types
     * @param string[] $tags
     * @param TraceParentsSortParameters[] $sort
     */
    public function __construct(
        public int $page = 1,
        public ?int $perPage = null,
        public ?string $traceId = null,
        public bool $allTracesInTree = false,
        public ?PeriodParameters $loggingPeriod = null,
        public array $types = [],
        public array $tags = [],
        public ?TraceDataFilterParameters $data = null,
        public array $sort = []
    ) {
    }
}
