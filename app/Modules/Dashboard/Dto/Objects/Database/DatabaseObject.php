<?php

namespace App\Modules\Dashboard\Dto\Objects\Database;

class DatabaseObject
{
    /**
     * @param CollectionObject[] $collections
     */
    public function __construct(
        public string $name,
        public float $sizeInMb,
        public array $collections
    ) {
    }
}
