<?php

namespace App\Modules\Dashboard\Repositories\Database\Dto;

class DatabaseCollectionIndexDto
{
    public function __construct(
        public string $name,
        public float $size,
        public int $usage
    ) {
    }
}
