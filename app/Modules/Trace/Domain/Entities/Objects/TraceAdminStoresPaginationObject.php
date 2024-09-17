<?php

namespace App\Modules\Trace\Domain\Entities\Objects;

use App\Modules\Common\Domain\Entities\PaginationInfoObject;

readonly class TraceAdminStoresPaginationObject
{
    /**
     * @param TraceAdminStoreObject[] $items
     */
    public function __construct(
        public array $items,
        public PaginationInfoObject $paginationInfo
    ) {
    }
}
