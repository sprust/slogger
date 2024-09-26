<?php

namespace App\Modules\Cleaner\Domain\Entities\Objects;

use Illuminate\Support\Carbon;

readonly class ProcessObject
{
    public function __construct(
        public string $id,
        public int $settingId,
        public int $clearedCount,
        public ?string $error,
        public ?Carbon $clearedAt,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }
}
