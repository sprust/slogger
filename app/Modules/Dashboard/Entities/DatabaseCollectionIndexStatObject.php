<?php

namespace App\Modules\Dashboard\Entities;

class DatabaseCollectionIndexStatObject
{
    public function __construct(
        public string $name,
        public float $size,
        public int $usage
    ) {
    }
}
