<?php

namespace App\Modules\Trace\Domain\Entities\Objects;

use Illuminate\Support\Carbon;

readonly class TraceAdminStoreObject
{
    public function __construct(
        public string $id,
        public string $title,
        public int $storeVersion,
        public string $storeDataHash,
        public string $storeData,
        public int $creatorId,
        public ?Carbon $usedAt,
        public Carbon $createdAt
    ) {
    }
}
