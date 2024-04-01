<?php

namespace App\Modules\TraceAggregator\Repositories\Dto;

use App\Modules\Common\Pagination\PaginationInfoObject;

readonly class ServiceStatsPaginationDto
{
    /**
     * @param ServiceStatDto[] $items
     */
    public function __construct(
        public array $items,
        public PaginationInfoObject $paginationInfo
    ) {
    }
}
