<?php

namespace App\Modules\Dashboard\Dto\Objects\Database;

class CollectionIndexObject
{
    public function __construct(
        public string $name,
        public float $sizeInMb
    ) {
    }
}
