<?php

namespace App\Modules\Common\Pagination;

use App\Http\Resources\AbstractApiResource;

class PaginationResource extends AbstractApiResource
{
    private int $total;
    private int $per_page;
    private int $current_page;
    private int $total_pages;

    public function __construct(PaginationInfoObject $resource)
    {
        parent::__construct($resource);

        $this->total        = $resource->total;
        $this->per_page     = $resource->perPage;
        $this->current_page = $resource->currentPage;
        $this->total_pages  = $resource->totalPages;
    }
}
