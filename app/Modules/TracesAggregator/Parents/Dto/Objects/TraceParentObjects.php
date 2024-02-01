<?php

namespace App\Modules\TracesAggregator\Parents\Dto\Objects;

use App\Services\Dto\PaginationInfoObject;

readonly class TraceParentObjects
{
    /**
     * @param TraceParentObject[] $items
     */
    public function __construct(
        public array $items,
        public PaginationInfoObject $paginationInfo
    ) {
    }
}