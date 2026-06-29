<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Entities;

readonly class DatabaseCollectionIndexStatObject
{
    public function __construct(
        public string $name,
        public float $size,
        public int $usage
    ) {
    }
}
