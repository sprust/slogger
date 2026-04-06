<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Entities;

readonly class DatabaseStatCacheObject
{
    /**
     * @param DatabaseStatObject[] $stats
     */
    public function __construct(
        public string $cachedAt,
        public array $stats,
    ) {
    }
}
