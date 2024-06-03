<?php

namespace App\Modules\Trace\Domain\Entities\Objects\Timestamp;

use Illuminate\Support\Carbon;

class TraceTimestampMetricObject
{
    public function __construct(
        public string $key,
        public Carbon $value
    ) {
    }
}
