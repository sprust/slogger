<?php

namespace App\Modules\Trace\Domain\Entities\Objects;

readonly class TraceTypeCountedObject
{
    public function __construct(
        public string $type,
        public int $count
    ) {
    }
}
