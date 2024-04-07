<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Parameters\DataFilter;

readonly class TraceDataFilterBooleanParameters
{
    public function __construct(
        public bool $value
    ) {
    }
}
