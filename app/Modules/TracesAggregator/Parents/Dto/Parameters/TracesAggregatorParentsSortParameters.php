<?php

namespace App\Modules\TracesAggregator\Parents\Dto\Parameters;

use App\Modules\TracesAggregator\Enums\TracesAggregatorSortFieldEnum;
use App\Services\Enums\SortDirectionEnum;

readonly class TracesAggregatorParentsSortParameters
{
    public function __construct(
        public TracesAggregatorSortFieldEnum $fieldEnum,
        public SortDirectionEnum $directionEnum
    ) {
    }
}
