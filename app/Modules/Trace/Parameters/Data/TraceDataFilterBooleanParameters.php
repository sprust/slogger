<?php

namespace App\Modules\Trace\Parameters\Data;

readonly class TraceDataFilterBooleanParameters
{
    public function __construct(
        public bool $value
    ) {
    }
}
