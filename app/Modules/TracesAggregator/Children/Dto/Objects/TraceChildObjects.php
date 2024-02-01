<?php

namespace App\Modules\TracesAggregator\Children\Dto\Objects;

use App\Services\Dto\PaginationInfoObject;

readonly class TraceChildObjects
{
    /**
     * @param TraceChildObject[] $items
     */
    public function __construct(
        public array $items,
        public PaginationInfoObject $paginationInfo
    ) {
    }
}
