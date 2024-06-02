<?php

namespace App\Modules\TraceCollector\Repositories\Dto;

use Illuminate\Support\Carbon;

class TraceTimestampMetricDto
{
    public function __construct(
        public string $key,
        public Carbon $value
    ) {
    }
}
