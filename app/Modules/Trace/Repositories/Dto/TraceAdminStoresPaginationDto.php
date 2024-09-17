<?php

namespace App\Modules\Trace\Repositories\Dto;

use App\Modules\Common\Repositories\PaginationInfoDto;

readonly class TraceAdminStoresPaginationDto
{
    /**
     * @param TraceAdminStoreDto[] $items
     */
    public function __construct(
        public array $items,
        public PaginationInfoDto $paginationInfo
    ) {
    }
}
