<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters\Data;

readonly class TraceDataFilterBooleanParameters
{
    public function __construct(
        public bool $value
    ) {
    }
}
