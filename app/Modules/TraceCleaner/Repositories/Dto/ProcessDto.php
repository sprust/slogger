<?php

namespace App\Modules\TraceCleaner\Repositories\Dto;

use Illuminate\Support\Carbon;

readonly class ProcessDto
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
