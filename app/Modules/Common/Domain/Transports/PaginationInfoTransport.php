<?php

namespace App\Modules\Common\Domain\Transports;

use App\Modules\Common\Domain\Entities\PaginationInfoObject;
use App\Modules\Common\Repositories\PaginationInfoDto;

class PaginationInfoTransport
{
    public static function toObject(PaginationInfoDto $dto): PaginationInfoObject
    {
        return new PaginationInfoObject(
            total: $dto->total,
            perPage: $dto->perPage,
            currentPage: $dto->currentPage,
            totalPages: $dto->totalPages
        );
    }
}
