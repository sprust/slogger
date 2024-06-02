<?php

namespace App\Modules\Trace\Domain\Entities\Parameters\DataFilter;

readonly class TraceDataFilterBooleanParameters
{
    public function __construct(
        public bool $value
    ) {
    }
}
