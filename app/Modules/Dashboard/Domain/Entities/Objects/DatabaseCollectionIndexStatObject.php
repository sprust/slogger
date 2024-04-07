<?php

namespace App\Modules\Dashboard\Domain\Entities\Objects;

class DatabaseCollectionIndexStatObject
{
    public function __construct(
        public string $name,
        public float $size,
        public int $usage
    ) {
    }
}
