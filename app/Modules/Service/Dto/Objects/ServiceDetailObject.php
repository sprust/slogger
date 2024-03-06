<?php

namespace App\Modules\Service\Dto\Objects;

readonly class ServiceDetailObject
{
    public function __construct(
        public int $id,
        public string $name,
        public string $apiToken
    ) {
    }
}
