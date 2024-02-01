<?php

namespace App\Modules\TracesAggregator\Children\Dto\Parameters;

use App\Modules\TracesAggregator\Children\Enums\TraceChildrenSortFieldEnum;
use App\Services\Enums\SortDirectionEnum;

readonly class TraceChildrenSortParameters
{
    public function __construct(
        public TraceChildrenSortFieldEnum $fieldEnum,
        public SortDirectionEnum $directionEnum
    ) {
    }
}
