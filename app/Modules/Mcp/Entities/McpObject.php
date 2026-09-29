<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities;

use Illuminate\Support\Carbon;

readonly class McpObject
{
    public function __construct(
        public int $id,
        public string $name,
        public string $token,
        public bool $enabled,
        public int $requestsCount,
        public ?Carbon $lastUsedAt,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }
}
