<?php

namespace App\Modules\Service\Domain\Entities\Objects;

readonly class ServiceObject
{
    public function __construct(
        public int $id,
        public string $name,
        public string $apiToken
    ) {
    }
}
