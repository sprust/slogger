<?php

namespace App\Modules\Trace\Domain\Entities\Objects;

readonly class TraceStringFieldObject
{
    public function __construct(
        public string $name,
        public int $count
    ) {
    }
}
