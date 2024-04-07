<?php

namespace App\Modules\Dashboard\Domain\Entities\Objects;

readonly class ServiceObject
{
    public function __construct(
        public int $id,
        public string $name
    ) {
    }
}
