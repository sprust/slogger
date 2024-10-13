<?php

namespace App\Modules\Dashboard\Entities;

class DatabaseStatObject
{
    /**
     * @param DatabaseCollectionStatObject[] $collections
     */
    public function __construct(
        public string $name,
        public float $size,
        public float $memoryUsage,
        public array $collections
    ) {
    }
}
