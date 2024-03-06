<?php

namespace App\Modules\Dashboard\Dto\Objects\Database;

class CollectionObject
{
    /**
     * @param CollectionIndexObject[] $indexes
     */
    public function __construct(
        public string $name,
        public float $sizeInMb,
        public float $indexesSizeInMb,
        public float $totalSizeInMb,
        public int $count,
        public float $avgObjSizeInMb,
        public array $indexes,
    ) {
    }
}
