<?php

namespace App\Modules\TraceCleaner\Domain\Entities\Objects;

use Illuminate\Support\Carbon;

readonly class SettingObject
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
