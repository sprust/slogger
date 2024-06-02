<?php

namespace App\Modules\Trace\Domain\Entities\Parameters\Data;

use App\Modules\Trace\Enums\TraceDataFilterCompNumericTypeEnum;

readonly class TraceDataFilterNumericParameters
{
    public function __construct(
        public int|float $value,
        public TraceDataFilterCompNumericTypeEnum $comp
    ) {
    }
}
