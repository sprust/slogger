<?php

namespace App\Modules\Dashboard\Repositories\Database\Dto;

class DatabaseDto
{
    /**
     * @param DatabaseCollectionDto[] $collections
     */
    public function __construct(
        public string $name,
        public float $size,
        public array $collections
    ) {
    }
}
