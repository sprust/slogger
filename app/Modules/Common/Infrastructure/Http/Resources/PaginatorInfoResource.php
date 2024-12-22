<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Http\Resources;

use App\Modules\Common\Entities\PaginationInfoObject;

class PaginatorInfoResource extends AbstractApiResource
{
    private int $total;
    private int $per_page;
    private int $current_page;
    private int $total_pages;

    public function __construct(PaginationInfoObject $paginatorInfo)
    {
        parent::__construct($paginatorInfo);

        $this->total        = $paginatorInfo->total;
        $this->per_page     = $paginatorInfo->perPage;
        $this->current_page = $paginatorInfo->currentPage;
        $this->total_pages  = $paginatorInfo->totalPages;
    }
}
