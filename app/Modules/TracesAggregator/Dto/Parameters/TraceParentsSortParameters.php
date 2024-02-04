<?php

namespace App\Modules\TracesAggregator\Dto\Parameters;

use App\Modules\TracesAggregator\Enums\TraceParentsSortFieldEnum;
use App\Services\Enums\SortDirectionEnum;

readonly class TraceParentsSortParameters
{
    public function __construct(
        public TraceParentsSortFieldEnum $fieldEnum,
        public SortDirectionEnum $directionEnum
    ) {
    }
}
