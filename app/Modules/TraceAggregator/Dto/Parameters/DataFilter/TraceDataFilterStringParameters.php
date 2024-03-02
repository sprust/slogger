<?php

namespace App\Modules\TraceAggregator\Dto\Parameters\DataFilter;

use App\Modules\TraceAggregator\Enums\TraceDataFilterCompStringTypeEnum;

readonly class TraceDataFilterStringParameters
{
    public function __construct(
        public ?string $value,
        public TraceDataFilterCompStringTypeEnum $comp
    ) {
    }
}
