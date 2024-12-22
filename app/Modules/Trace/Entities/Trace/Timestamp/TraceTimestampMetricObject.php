<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Timestamp;

use Illuminate\Support\Carbon;

class TraceTimestampMetricObject
{
    public function __construct(
        public string $key,
        public Carbon $value
    ) {
    }
}
