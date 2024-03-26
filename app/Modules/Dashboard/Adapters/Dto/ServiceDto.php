<?php

namespace App\Modules\Dashboard\Adapters\Dto;

readonly class ServiceDto
{
    public function __construct(
        public int $id,
        public string $name
    ) {
    }
}
