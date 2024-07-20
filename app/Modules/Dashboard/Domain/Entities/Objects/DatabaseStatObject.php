<?php

namespace App\Modules\Dashboard\Domain\Entities\Objects;

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
