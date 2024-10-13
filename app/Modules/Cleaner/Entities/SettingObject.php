<?php

namespace App\Modules\Cleaner\Entities;

use Illuminate\Support\Carbon;

readonly class SettingObject
{
    public function __construct(
        public int $id,
        public int $daysLifetime,
        public ?string $type,
        public bool $onlyData,
        public bool $deleted,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }
}
