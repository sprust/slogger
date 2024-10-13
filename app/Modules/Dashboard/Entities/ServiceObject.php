<?php

namespace App\Modules\Dashboard\Entities;

readonly class ServiceObject
{
    public function __construct(
        public int $id,
        public string $name
    ) {
    }
}
