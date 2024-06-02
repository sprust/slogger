<?php

namespace App\Modules\Trace\Repositories\Dto;

use Illuminate\Support\Carbon;

class TraceTimestampMetricDto
{
    public function __construct(
        public string $key,
        public Carbon $value
    ) {
    }
}
