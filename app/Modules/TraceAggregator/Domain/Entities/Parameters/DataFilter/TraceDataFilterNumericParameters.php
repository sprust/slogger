<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Parameters\DataFilter;

use App\Modules\TraceAggregator\Domain\Enums\TraceDataFilterCompNumericTypeEnum;

readonly class TraceDataFilterNumericParameters
{
    public function __construct(
        public int|float $value,
        public TraceDataFilterCompNumericTypeEnum $comp
    ) {
    }
}
