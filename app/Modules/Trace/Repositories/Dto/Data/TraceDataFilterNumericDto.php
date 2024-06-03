<?php

namespace App\Modules\Trace\Repositories\Dto\Data;

use App\Modules\Trace\Enums\TraceDataFilterCompNumericTypeEnum;

readonly class TraceDataFilterNumericDto
{
    public function __construct(
        public int|float $value,
        public TraceDataFilterCompNumericTypeEnum $comp
    ) {
    }
}
