<?php

namespace App\Modules\Dashboard\Repositories\Database\Dto;

class DatabaseCollectionDto
{
    /**
     * @param DatabaseCollectionIndexDto[] $indexes
     */
    public function __construct(
        public string $name,
        public float $size,
        public float $indexesSize,
        public float $totalSize,
        public int $count,
        public float $avgObjSize,
        public array $indexes
    ) {
    }
}
