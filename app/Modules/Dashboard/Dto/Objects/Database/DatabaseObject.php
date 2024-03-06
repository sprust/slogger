<?php

namespace App\Modules\Dashboard\Dto\Objects\Database;

class DatabaseObject
{
    /**
     * @param DatabaseCollectionObject[] $collections
     */
    public function __construct(
        public string $name,
        public float $size,
        public array $collections
    ) {
    }
}
