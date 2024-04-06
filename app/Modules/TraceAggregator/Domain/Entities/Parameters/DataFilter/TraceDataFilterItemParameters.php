<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Parameters\DataFilter;

readonly class TraceDataFilterItemParameters
{
    public function __construct(
        public string $field,
        public ?bool $null,
        public ?TraceDataFilterNumericParameters $numeric,
        public ?TraceDataFilterStringParameters $string,
        public ?TraceDataFilterBooleanParameters $boolean
    ) {
    }
}
