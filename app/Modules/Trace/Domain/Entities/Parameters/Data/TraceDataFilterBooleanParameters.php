<?php

namespace App\Modules\Trace\Domain\Entities\Parameters\Data;

readonly class TraceDataFilterBooleanParameters
{
    public function __construct(
        public bool $value
    ) {
    }
}
