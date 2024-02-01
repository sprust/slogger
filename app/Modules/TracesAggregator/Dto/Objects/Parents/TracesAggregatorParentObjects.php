<?php

namespace App\Modules\TracesAggregator\Dto\Objects\Parents;

use App\Services\Dto\PaginationInfoObject;

readonly class TracesAggregatorParentObjects
{
    /**
     * @param TracesAggregatorParentObject[] $items
     */
    public function __construct(
        public array $items,
        public PaginationInfoObject $paginationInfo
    ) {
    }
}
