<?php

namespace App\Modules\TracesAggregator\Parents\Dto\Parameters;

use App\Modules\TracesAggregator\Dto\PeriodParameters;

readonly class TraceParentsFindParameters
{
    /**
     * @param TraceParentsSortParameters[] $sort
     */
    public function __construct(
        public int $page = 1,
        public ?int $perPage = null,
        public ?string $type = null,
        public ?PeriodParameters $loggingPeriod = null,
        public array $sort = []
    ) {
    }
}
