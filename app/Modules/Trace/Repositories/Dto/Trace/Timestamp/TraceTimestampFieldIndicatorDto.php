<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Trace\Timestamp;

class TraceTimestampFieldIndicatorDto
{
    public function __construct(
        public string $name,
        public int|float $value
    ) {
    }
}
