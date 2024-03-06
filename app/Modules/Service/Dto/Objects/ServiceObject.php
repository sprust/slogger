<?php

namespace App\Modules\Service\Dto\Objects;

readonly class ServiceObject
{
    public function __construct(
        public int $id,
        public string $name
    ) {
    }
}
