<?php

namespace App\Modules\Trace\Repositories\Dto;

use App\Modules\Common\Entities\PaginationInfoObject;

readonly class TraceAdminStoresPaginationDto
{
    /**
     * @param TraceAdminStoreDto[] $items
     */
    public function __construct(
        public array $items,
        public PaginationInfoObject $paginationInfo
    ) {
    }
}
