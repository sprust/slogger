<?php

namespace App\Modules\TracesAggregator\Parents\Dto\Parameters;

use App\Modules\TracesAggregator\Parents\Enums\TracesAggregatorParentsSortFieldEnum;
use App\Services\Enums\SortDirectionEnum;

readonly class TracesAggregatorParentsSortParameters
{
    public function __construct(
        public TracesAggregatorParentsSortFieldEnum $fieldEnum,
        public SortDirectionEnum $directionEnum
    ) {
    }
}
