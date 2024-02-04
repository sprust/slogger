<?php

namespace App\Services\Dto;

class PaginationInfoObject
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
