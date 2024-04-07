<?php

namespace App\Modules\Dashboard\Repositories\Dto;

class DatabaseCollectionIndexStatDto
{
    public function __construct(
        public string $name,
        public float $size,
        public int $usage
    ) {
    }
}
