<?php

namespace App\Modules\Dashboard\Dto\Objects\Database;

class DatabaseCollectionIndexObject
{
    public function __construct(
        public string $name,
        public float $size,
        public int $usage
    ) {
    }
}
