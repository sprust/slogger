<?php

namespace App\Modules\Trace\Entities\Trace;

use App\Modules\Common\Entities\PaginationInfoObject;

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
