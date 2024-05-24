<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

use Illuminate\Support\Carbon;

readonly class TraceTimestampMetricsObject
{
    public function __construct(
        public Carbon $m,
        public Carbon $d,
        public Carbon $h12,
        public Carbon $h4,
        public Carbon $h,
        public Carbon $min30,
        public Carbon $min10,
        public Carbon $min5,
        public Carbon $min,
        public Carbon $s30,
        public Carbon $s10,
        public Carbon $s5
    ) {
    }
}
