<?php

namespace App\Modules\TraceAggregator\Dto\Parameters\DataFilter;

use App\Modules\TraceAggregator\Enums\TraceDataFilterCompNumericTypeEnum;

readonly class TraceDataFilterNumericParameters
{
    public function __construct(
        public int|float $value,
        public TraceDataFilterCompNumericTypeEnum $comp
    ) {
    }
}
