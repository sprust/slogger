<?php

namespace App\Modules\TracesAggregator\Dto\Objects;

use App\Services\Dto\PaginationInfoObject;

readonly class TraceParentTypeObjects
{
    /**
     * @param TraceParentTypeObject[] $items
     */
    public function __construct(
        public array $items,
        public PaginationInfoObject $paginationInfo
    ) {
    }
}
