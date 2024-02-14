<?php

namespace App\Modules\TracesAggregator\Dto\Parameters\DataFilter;

use App\Modules\TracesAggregator\Enums\TraceDataFilterCompStringTypeEnum;

readonly class TraceDataFilterStringParameters
{
    public function __construct(
        public ?string $value,
        public TraceDataFilterCompStringTypeEnum $comp
    ) {
    }
}
