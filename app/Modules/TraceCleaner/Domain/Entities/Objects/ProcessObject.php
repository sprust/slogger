<?php

namespace App\Modules\TraceCleaner\Domain\Entities\Objects;

use Illuminate\Support\Carbon;

readonly class ProcessObject
{
    public function __construct(
        public int $id,
        public int $settingId,
        public int $clearedCount,
        public ?Carbon $clearedAt,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }
}
