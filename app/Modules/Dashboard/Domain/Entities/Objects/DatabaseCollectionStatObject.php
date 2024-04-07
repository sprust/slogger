<?php

namespace App\Modules\Dashboard\Domain\Entities\Objects;

class DatabaseCollectionStatObject
{
    /**
     * @param DatabaseCollectionIndexStatObject[] $indexes
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
