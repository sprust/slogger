<?php

namespace App\Modules\TraceAggregator\Dto\Parameters\DataFilter;

readonly class TraceDataFilterBooleanParameters
{
    public function __construct(
        public bool $value
    ) {
    }
}
