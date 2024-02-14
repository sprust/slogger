<?php

namespace App\Modules\TracesAggregator\Dto\Parameters\DataFilter;

use App\Modules\TracesAggregator\Enums\TraceDataFilterCompNumericTypeEnum;

readonly class TraceDataFilterNumericParameters
{
    public function __construct(
        public int|float $value,
        public TraceDataFilterCompNumericTypeEnum $comp
    ) {
    }
}
