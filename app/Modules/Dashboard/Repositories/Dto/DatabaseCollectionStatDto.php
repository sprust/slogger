<?php

namespace App\Modules\Dashboard\Repositories\Dto;

class DatabaseCollectionStatDto
{
    /**
     * @param DatabaseCollectionIndexStatDto[] $indexes
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
