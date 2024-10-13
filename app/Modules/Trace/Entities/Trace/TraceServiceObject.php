<?php

namespace App\Modules\Trace\Entities\Trace;

readonly class TraceServiceObject
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
