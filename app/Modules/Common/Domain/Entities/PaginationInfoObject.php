<?php

namespace App\Modules\Common\Domain\Entities;

class PaginationInfoObject
{
    public function __construct(
        public int $total,
        public int $perPage,
        public int $currentPage,
        public int $totalPages
    ) {
    }
}
