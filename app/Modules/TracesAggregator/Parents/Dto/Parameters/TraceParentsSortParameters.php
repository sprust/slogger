<?php

namespace App\Modules\TracesAggregator\Parents\Dto\Parameters;

use App\Modules\TracesAggregator\Parents\Enums\TraceParentsSortFieldEnum;
use App\Services\Enums\SortDirectionEnum;

readonly class TraceParentsSortParameters
{
    public function __construct(
        public TraceParentsSortFieldEnum $fieldEnum,
        public SortDirectionEnum $directionEnum
    ) {
    }
}
