<?php

namespace App\Modules\Trace\Repositories\Dto;

use Illuminate\Support\Carbon;

readonly class TraceAdminStoreDto
{
    public function __construct(
        public string $id,
        public string $title,
        public int $storeVersion,
        public string $storeDataHash,
        public string $storeData,
        public Carbon $createdAt
    ) {
    }
}
