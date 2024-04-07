<?php

namespace App\Modules\Service\Repositories\Dto;

readonly class ServiceDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $apiToken
    ) {
    }
}
