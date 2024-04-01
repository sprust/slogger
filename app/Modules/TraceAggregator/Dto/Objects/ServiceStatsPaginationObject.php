<?php

namespace App\Modules\TraceAggregator\Dto\Objects;

use App\Modules\Common\Pagination\PaginationInfoObject;

readonly class ServiceStatsPaginationObject
{
    /**
     * @param ServiceStatObject[] $items
     */
    public function __construct(
        public array $items,
        public PaginationInfoObject $paginationInfo
    ) {
    }
}
