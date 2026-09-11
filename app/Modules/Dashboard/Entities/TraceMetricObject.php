<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Entities;

use Illuminate\Support\Carbon;

readonly class TraceMetricObject
{
    public function __construct(
        public string $type,
        public Carbon $timestamp,
        public int $logged,
        public int $buffered,
        public int $stored
    ) {
    }
}
