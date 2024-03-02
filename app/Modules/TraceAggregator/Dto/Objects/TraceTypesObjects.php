<?php

namespace App\Modules\TraceAggregator\Dto\Objects;

use App\Services\Dto\PaginationInfoObject;

readonly class TraceTypesObjects
{
    /**
     * @param TraceItemTypeObject[] $items
     */
    public function __construct(
        public array $items,
        public PaginationInfoObject $paginationInfo
    ) {
    }
}
