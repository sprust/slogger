<?php

namespace App\Modules\TraceCleaner\Repositories\Dto;

use Illuminate\Support\Carbon;

readonly class SettingDto
{
    public function __construct(
        public int $id,
        public int $daysLifetime,
        public ?string $type,
        public bool $deleted,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }
}
