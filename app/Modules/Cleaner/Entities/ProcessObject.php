<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Entities;

use Illuminate\Support\Carbon;

readonly class ProcessObject
{
    public function __construct(
        public string $id,
        public int $clearedCollectionsCount,
        public int $clearedTracesCount,
        public ?string $error,
        public ?Carbon $clearedAt,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }
}
