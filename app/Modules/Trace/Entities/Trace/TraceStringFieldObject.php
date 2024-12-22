<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace;

readonly class TraceStringFieldObject
{
    public function __construct(
        public string $name,
        public int $count
    ) {
    }
}
