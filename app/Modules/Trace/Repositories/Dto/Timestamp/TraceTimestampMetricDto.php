<?php

namespace App\Modules\Trace\Repositories\Dto\Timestamp;

use Illuminate\Support\Carbon;

class TraceTimestampMetricDto
{
    public function __construct(
        public string $key,
        public Carbon $value
    ) {
    }
}
