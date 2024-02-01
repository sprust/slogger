<?php

namespace App\Services\Dto;

class PaginationInfoObject
{
    public function __construct(
        public int $total,
        public int $perPage,
        public int $currentPage
    ) {
    }
}
