<?php

namespace App\Modules\Trace\Entities\Trace;

readonly class TraceTypeCountedObject
{
    public function __construct(
        public string $type,
        public int $count
    ) {
    }
}
