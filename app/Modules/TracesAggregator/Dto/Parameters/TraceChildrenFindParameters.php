<?php

namespace App\Modules\TracesAggregator\Dto\Parameters;

use App\Modules\TracesAggregator\Dto\PeriodParameters;

readonly class TraceChildrenFindParameters
{
    /**
     * @param string[]                      $types
     * @param TraceChildrenSortParameters[] $sort
     */
    public function __construct(
        public string $parentTraceId,
        public int $page = 1,
        public ?int $perPage = null,
        public array $types = [],
        public ?PeriodParameters $loggingPeriod = null,
        public array $sort = []
    ) {
    }
}
