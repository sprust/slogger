<?php

namespace App\Modules\Dashboard\Repositories\Dto;

class DatabaseStatDto
{
    /**
     * @param DatabaseCollectionStatDto[] $collections
     */
    public function __construct(
        public string $name,
        public float $size,
        public array $collections
    ) {
    }
}
