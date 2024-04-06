<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

use App\Services\Dto\PaginationInfoObject;

readonly class TraceItemObjects
{
    /**
     * @param TraceItemObject[] $items
     */
    public function __construct(
        public array $items,
        public PaginationInfoObject $paginationInfo
    ) {
    }
}
