<?php

declare(strict_types=1);

namespace App\Modules\Service\Entities;

use Illuminate\Support\Carbon;

readonly class ServiceObject
{
    public function __construct(
        public int $id,
        public string $name,
        public string $uniqueKey,
        public string $apiToken,
        public int $position,
        public Carbon $createdAt
    ) {
    }
}
