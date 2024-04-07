<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Parameters\DataFilter;

use App\Modules\TraceAggregator\Domain\Enums\TraceDataFilterCompStringTypeEnum;

readonly class TraceDataFilterStringParameters
{
    public function __construct(
        public ?string $value,
        public TraceDataFilterCompStringTypeEnum $comp
    ) {
    }
}
