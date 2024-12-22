<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace;

readonly class TraceServiceObject
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
