<?php

declare(strict_types=1);

namespace App\Modules\Common\Entities;

readonly class PaginationInfoObject
{
    public int $totalPages;

    public function __construct(
        public int $total,
        public int $perPage,
        public int $currentPage
    ) {
        $this->totalPages = (int) ceil($this->total / $this->perPage);
    }
}
