<?php

namespace App\Modules\Service\Repository\Dto;

readonly class ServiceDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $apiToken
    ) {
    }
}
