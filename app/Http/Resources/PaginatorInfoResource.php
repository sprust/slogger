<?php

namespace App\Http\Resources;

use App\Services\Dto\PaginationInfoObject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaginatorInfoResource extends JsonResource
{
    public function __construct(PaginationInfoObject $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var PaginationInfoObject $paginatorInfo */
        $paginatorInfo = $this->resource;

        return [
            'total'        => $paginatorInfo->total,
            'per_page'     => $paginatorInfo->perPage,
            'current_page' => $paginatorInfo->currentPage,
            'total_pages'  => $paginatorInfo->totalPages,
        ];
    }
}
