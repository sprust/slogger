<?php

namespace App\Modules\Common\Repositories;

class PaginationInfoDto
{
    public int $totalPages;

    public function __construct(
        public int $total,
        public int $perPage,
        public int $currentPage
    ) {
        $this->totalPages = ceil($this->total / $this->perPage);
    }
}
