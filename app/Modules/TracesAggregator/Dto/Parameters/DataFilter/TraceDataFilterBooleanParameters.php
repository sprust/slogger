<?php

namespace App\Modules\TracesAggregator\Dto\Parameters\DataFilter;

readonly class TraceDataFilterBooleanParameters
{
    public function __construct(
        public bool $value
    ) {
    }
}
