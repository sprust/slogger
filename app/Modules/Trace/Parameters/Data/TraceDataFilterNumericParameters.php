<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters\Data;

use App\Modules\Trace\Enums\TraceDataFilterCompNumericTypeEnum;

readonly class TraceDataFilterNumericParameters
{
    public function __construct(
        public int|float $value,
        public TraceDataFilterCompNumericTypeEnum $comp
    ) {
    }
}
