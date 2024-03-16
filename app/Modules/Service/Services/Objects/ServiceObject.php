<?php

namespace App\Modules\Service\Services\Objects;

readonly class ServiceObject
{
    public function __construct(
        public int $id,
        public string $name,
        public string $apiToken
    ) {
    }
}
