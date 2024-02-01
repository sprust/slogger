<?php

namespace App\Modules\TracesAggregator\Parents\Dto\Objects\Parents;

use App\Services\Dto\PaginationInfoObject;

readonly class TracesAggregatorParentTypeObjects
{
    /**
     * @param TracesAggregatorParentTypeObject[] $items
     */
    public function __construct(
        public array $items,
        public PaginationInfoObject $paginationInfo
    ) {
    }
}
