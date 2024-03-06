<?php

namespace App\Modules\Dashboard\Dto\Objects\Database;

class DatabaseCollectionObject
{
    /**
     * @param DatabaseCollectionIndexObject[] $indexes
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
